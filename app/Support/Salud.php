<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Chequeos de salud del sitio para el panel de monitoreo.
 * Cada chequeo: ['t' => título, 'e' => 'ok'|'warn'|'bad'|'info', 'd' => detalle, 'fix' => cómo arreglarlo (opcional)]
 */
class Salud
{
    public static function bytes(int $b): string
    {
        return $b >= 1048576 ? round($b / 1048576, 1).' MB' : max(1, round($b / 1024)).' KB';
    }

    /** Archivos de imagen/video pesados dentro de public/img y public/uploads. */
    public static function pesados(int $minKb = 500): array
    {
        $out = [];
        foreach (['img', 'uploads'] as $dir) {
            $base = public_path($dir);
            if (! is_dir($base)) {
                continue;
            }
            foreach (File::allFiles($base) as $f) {
                $ext = strtolower($f->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) && $f->getSize() > $minKb * 1024) {
                    $out[] = ['ruta' => $dir.'/'.str_replace('\\', '/', $f->getRelativePathname()), 'bytes' => $f->getSize()];
                }
            }
        }
        usort($out, fn ($a, $b) => $b['bytes'] <=> $a['bytes']);

        return $out;
    }

    public static function pesoVideo(): ?array
    {
        foreach (['hero-video.mp4', 'pag web.mp4'] as $v) {
            if (is_file(public_path('img/'.$v))) {
                return ['archivo' => $v, 'bytes' => filesize(public_path('img/'.$v))];
            }
        }

        return null;
    }

    private static function carpetaBytes(string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }
        $t = 0;
        foreach (File::allFiles($dir) as $f) {
            $t += $f->getSize();
        }

        return $t;
    }

    public static function revisar(): array
    {
        $r = [];
        $add = function (string $grupo, string $t, string $e, string $d, ?string $fix = null) use (&$r) {
            $r[$grupo][] = ['t' => $t, 'e' => $e, 'd' => $d, 'fix' => $fix];
        };

        // ---- Sistema ----
        try {
            DB::select('select 1');
            $add('sistema', 'Base de datos', 'ok', 'Conectada ('.DB::getDriverName().').');
        } catch (\Throwable $e) {
            $add('sistema', 'Base de datos', 'bad', 'No responde: '.$e->getMessage());
        }

        $prod = app()->environment('production');
        if ($prod && config('app.debug')) {
            $add('sistema', 'Modo depuración', 'bad', 'APP_DEBUG está en true en producción: muestra errores internos a los visitantes.', 'En el .env del servidor: APP_DEBUG=false');
        } else {
            $add('sistema', 'Modo depuración', $prod ? 'ok' : 'info', $prod ? 'Desactivado en producción.' : 'Estás en entorno "'.app()->environment().'" (normal en tu computadora).');
        }

        $host = request()->getHost();
        $local = in_array($host, ['localhost', '127.0.0.1'], true) || str_ends_with($host, '.test');
        if ($local) {
            $add('sistema', 'HTTPS', 'info', 'Estás en local; se revisa cuando el sitio esté publicado.');
        } elseif (request()->isSecure()) {
            $add('sistema', 'HTTPS (candado)', 'ok', 'La página se sirve de forma segura.');
        } else {
            $add('sistema', 'HTTPS (candado)', 'bad', 'El sitio se abrió sin HTTPS. Google y los navegadores lo marcan como no seguro.', 'Activa el SSL gratuito en Hostinger y pon APP_URL=https://tu-dominio en el .env.');
        }
        if (! $local && rtrim((string) config('app.url'), '/') !== rtrim(request()->getSchemeAndHttpHost(), '/')) {
            $add('sistema', 'APP_URL', 'warn', 'APP_URL ('.config('app.url').') no coincide con el dominio actual: afecta el sitemap y los enlaces compartidos.', 'Corrige APP_URL en el .env y ejecuta php artisan config:clear');
        }

        $mailer = (string) config('mail.default');
        $add('sistema', 'Correo de reservas y contacto', in_array($mailer, ['log', 'array'], true) ? 'warn' : 'ok',
            in_array($mailer, ['log', 'array'], true) ? 'MAIL_MAILER="'.$mailer.'": los avisos de nuevas reservas NO salen por correo.' : 'Salida configurada ('.$mailer.').',
            in_array($mailer, ['log', 'array'], true) ? 'Configura MAIL_* con el SMTP de tu hosting.' : null);

        $logRuta = storage_path('logs/laravel.log');
        $logB = is_file($logRuta) ? filesize($logRuta) : 0;
        $add('sistema', 'Registro de errores', $logB > 20 * 1048576 ? 'warn' : 'ok', 'laravel.log pesa '.self::bytes($logB).'.', $logB > 20 * 1048576 ? 'Vacía o rota el archivo storage/logs/laravel.log.' : null);

        $add('sistema', 'PHP', version_compare(PHP_VERSION, '8.2.0', '>=') ? 'ok' : 'warn', 'Versión '.PHP_VERSION.'.');

        // ---- Imágenes y rendimiento ----
        $add('rendimiento', 'Extensión GD (optimizar fotos)', Imagenes::disponible() ? 'ok' : 'bad',
            Imagenes::disponible() ? 'Activa.' : 'No está activa: las fotos de pasajeros no se pueden reducir en el servidor.', Imagenes::disponible() ? null : 'Activa la extensión "gd" en el panel de PHP del hosting.');
        $add('rendimiento', 'Formato WebP', Imagenes::soportaWebp() ? 'ok' : 'warn',
            Imagenes::soportaWebp() ? 'Las fotos nuevas se guardan en WebP (≈30% más livianas).' : 'Este servidor no genera WebP; se usará JPG.');

        $sub = self::carpetaBytes(public_path('uploads'));
        $add('rendimiento', 'Fotos subidas (pasajeros, tours, blog)', $sub > 800 * 1048576 ? 'warn' : 'ok', 'Ocupan '.self::bytes($sub).' en public/uploads.', $sub > 800 * 1048576 ? 'Revisa si el plan de hosting tiene espacio suficiente.' : null);

        $pes = self::pesados(500);
        if ($pes) {
            $lista = implode(', ', array_map(fn ($p) => $p['ruta'].' ('.self::bytes($p['bytes']).')', array_slice($pes, 0, 4)));
            $add('rendimiento', 'Imágenes pesadas (>500 KB)', 'warn', count($pes).' archivo(s): '.$lista.(count($pes) > 4 ? '…' : ''), 'Ejecuta: php artisan mayo:imagenes --aplicar');
        } else {
            $add('rendimiento', 'Imágenes pesadas (>500 KB)', 'ok', 'Ninguna. Todas las imágenes están livianas.');
        }

        if ($v = self::pesoVideo()) {
            $mb = $v['bytes'] / 1048576;
            $add('rendimiento', 'Video del inicio', $mb > 15 ? 'warn' : 'ok', $v['archivo'].' pesa '.self::bytes($v['bytes']).'. No se descarga en celulares ni con conexión lenta.',
                $mb > 15 ? 'Reduce a 720p: ffmpeg -i entrada.mp4 -vf scale=-2:720 -an -crf 28 -movflags +faststart hero-video.mp4' : null);
        }

        $up = ini_get('upload_max_filesize');
        $add('rendimiento', 'Límite de subida de PHP', 'info', 'upload_max_filesize='.$up.', post_max_size='.ini_get('post_max_size').'. Las fotos de pasajeros llegan ya reducidas, así que no es problema.');

        return $r;
    }

    /** Puntaje 0–100 según los chequeos (ok=1, info=1, warn=.5, bad=0). */
    public static function puntaje(array $r): int
    {
        $t = 0;
        $n = 0;
        foreach ($r as $grupo) {
            foreach ($grupo as $c) {
                $n++;
                $t += match ($c['e']) { 'ok', 'info' => 1, 'warn' => 0.5, default => 0 };
            }
        }

        return $n ? (int) round($t / $n * 100) : 100;
    }
}
