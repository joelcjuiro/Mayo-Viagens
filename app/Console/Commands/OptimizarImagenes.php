<?php

namespace App\Console\Commands;

use App\Models\Tour;
use App\Models\TourItinerario;
use App\Support\Imagenes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Analiza public/img y deja las imágenes listas para internet:
 *  - reduce las pesadas (WebP, máx. 1600 px),
 *  - corrige nombres con espacios, tildes, ñ o caracteres dañados (Linux distingue mayúsculas y rompe con ellos),
 *  - actualiza las rutas guardadas en la base de datos,
 *  - y NO borra nada: los originales quedan en storage/app/imagenes-originales/.
 *
 * Sin --aplicar solo muestra el plan.
 */
class OptimizarImagenes extends Command
{
    protected $signature = 'mayo:imagenes
        {--aplicar : Hace los cambios (sin esto solo muestra qué haría)}
        {--forzar-webp : Convierte TODAS las fotos a WebP, aunque ya sean livianas}';

    protected $description = 'Analiza y optimiza las imágenes de public/img (con copia de seguridad y sin borrar nada)';

    /** Logos, íconos y sellos: se mantienen con su nombre y formato; solo se reducen si pesan de más. */
    private const FIJAS = '/^(logo|og|esnna|favicon|iso|whatsapp|facebook|instagram)/i';

    public function handle(): int
    {
        if (! Imagenes::disponible()) {
            $this->error('Falta la extensión GD de PHP. Actívala en php.ini (extension=gd) y vuelve a intentar.');

            return self::FAILURE;
        }

        $aplicar = (bool) $this->option('aplicar');
        $webp = Imagenes::soportaWebp();
        $base = public_path('img');
        if (! is_dir($base)) {
            $this->error('No existe la carpeta public/img.');

            return self::FAILURE;
        }

        $codigo = $this->codigoFuente();
        $planes = [];
        $destinosUsados = [];
        $todos = [];

        foreach (File::allFiles($base) as $f) {
            $rel = str_replace('\\', '/', $f->getRelativePathname());
            if (str_starts_with($rel, '_originales')) {
                continue;
            }
            $ext = strtolower($f->getExtension());
            $nombre = $f->getFilenameWithoutExtension();
            $todos[$rel] = $nombre;

            $fila = ['rel' => $rel, 'ruta' => $f->getPathname(), 'bytes' => $f->getSize(), 'ext' => $ext, 'nombre' => $nombre,
                'accion' => 'ok', 'destino' => null, 'nota' => []];

            if ($ext === 'mp4' || $ext === 'webm') {
                $mb = $f->getSize() / 1048576;
                $fila['nota'][] = $mb > 8 ? 'video pesado: redúcelo a 720p (ver comando al final)' : 'video OK';
                $planes[] = $fila;

                continue;
            }
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                continue;
            }

            $dir = str_contains($rel, '/') ? dirname($rel).'/' : '';
            $fija = (bool) preg_match(self::FIJAS, $nombre);
            $slug = $this->limpio($nombre);
            $nombreOk = $nombre === $slug;
            $kb = $f->getSize() / 1024;
            $esHero = (bool) preg_match('/^(hero|dashboard-hero)/i', $nombre);

            if ($fija) {
                // Mismo nombre y mismo formato
                $limite = preg_match('/^og/i', $nombre) ? 200 : 100;
                if ($kb > $limite && in_array($ext, ['jpg', 'jpeg'], true)) {
                    $fila += ['lado' => preg_match('/^og/i', $nombre) ? 1200 : 700, 'calidad' => 82, 'max' => $limite * 1024, 'formato' => 'jpeg'];
                    $fila['accion'] = 'reducir';
                    $fila['destino'] = $fila['ruta'];
                    $fila['nota'][] = 'logo/sello pesado: se reduce sin cambiar nombre';
                } elseif ($kb > $limite) {
                    $fila['nota'][] = 'pesa '.round($kb).' KB (PNG/WebP con posible transparencia: no se toca)';
                }
            } else {
                $pesada = $kb > 350;
                $pngFoto = $ext === 'png' && $kb > 150;
                if ($pesada || $pngFoto || ! $nombreOk || $this->option('forzar-webp')) {
                    if (! ($ext === 'webp' && $nombreOk && ! $pesada)) {
                        $nuevaExt = $webp ? 'webp' : 'jpg';
                        $destRel = $dir.$slug.'.'.$nuevaExt;
                        $n = 2;
                        while ((is_file(public_path('img/'.$destRel)) && realpath(public_path('img/'.$destRel)) !== realpath($f->getPathname())) || isset($destinosUsados[$destRel])) {
                            $destRel = $dir.$slug.'-'.$n++.'.'.$nuevaExt;
                        }
                        $destinosUsados[$destRel] = true;
                        $fila += ['lado' => $esHero ? 1920 : 1600, 'calidad' => 80, 'max' => ($esHero ? 320 : 250) * 1024, 'formato' => 'webp'];
                        $fila['accion'] = 'convertir';
                        $fila['destino'] = public_path('img/'.$destRel);
                        $fila['destRel'] = $destRel;
                        if (! $nombreOk) {
                            $fila['nota'][] = 'nombre corregido';
                        }
                    }
                }
            }

            if (! str_contains($rel, '/') && ! $this->seUsa($codigo, $nombre)) {
                $fila['nota'][] = 'POSIBLE SIN USO en el código (revisa si puedes quitarlo del repositorio)';
            }
            $planes[] = $fila;
        }

        // Posibles copias con el nombre dañado (ej. "monta?a" junto a "montaña")
        foreach ($planes as &$p) {
            if ($this->nombreDanado($p['nombre'])) {
                $mejor = null;
                $pct = 0;
                foreach ($todos as $rel2 => $n2) {
                    if ($rel2 === $p['rel'] || $this->nombreDanado($n2)) {
                        continue;
                    }
                    similar_text($this->limpio($p['nombre']), $this->limpio($n2), $x);
                    if ($x > $pct) {
                        $pct = $x;
                        $mejor = $rel2;
                    }
                }
                $p['nota'][] = 'nombre con caracteres dañados'.($mejor && $pct >= 80 ? ' · parece copia de «'.$mejor.'»' : '');
            }
        }
        unset($p);

        // ---- Mostrar el plan ----
        $filas = [];
        $antes = 0;
        foreach ($planes as $p) {
            $antes += $p['bytes'];
            $plan = match ($p['accion']) {
                'convertir' => '→ '.($p['destRel'] ?? ''),
                'reducir' => 'reducir (mismo nombre)',
                default => 'sin cambios',
            };
            $filas[] = [$p['rel'], $this->kb($p['bytes']), $plan, implode(' · ', $p['nota'])];
        }
        $this->table(['Archivo', 'Peso', 'Plan', 'Notas'], $filas);

        $trabajo = collect($planes)->whereIn('accion', ['convertir', 'reducir'])->count();
        $this->line('Imágenes analizadas: '.count($planes).' · peso total: '.$this->kb($antes).' · con cambios previstos: '.$trabajo);

        if (! $aplicar) {
            $this->newLine();
            $this->info('Esto fue solo el plan. Para aplicarlo (con copia de seguridad) ejecuta:  php artisan mayo:imagenes --aplicar');
            $this->videoAyuda($planes);

            return self::SUCCESS;
        }

        if ($trabajo === 0) {
            $this->info('No hay nada que cambiar.');
            $this->videoAyuda($planes);

            return self::SUCCESS;
        }

        // ---- Aplicar ----
        $respaldo = storage_path('app/imagenes-originales/'.date('Ymd-His'));
        $mapa = [];
        $despues = 0;
        $hechos = 0;
        foreach ($planes as $p) {
            if (! in_array($p['accion'], ['convertir', 'reducir'], true)) {
                $despues += $p['bytes'];

                continue;
            }
            $copia = $respaldo.'/'.$p['rel'];
            File::ensureDirectoryExists(dirname($copia));
            File::copy($p['ruta'], $copia);

            $tmp = $p['destino'].'.tmp';
            $r = Imagenes::convertir($p['ruta'], $tmp, $p['lado'], $p['calidad'], $p['max'], $p['formato']);
            if (! $r || ! is_file($tmp)) {
                File::delete($tmp);
                $this->warn('No se pudo leer '.$p['rel'].' (se deja como está).');
                $despues += $p['bytes'];

                continue;
            }
            $mismoLugar = realpath($p['destino']) === realpath($p['ruta']);
            if ($mismoLugar && $r['bytes'] >= $p['bytes']) {
                File::delete($tmp); // reducir no ayudó: se conserva el original
                $despues += $p['bytes'];

                continue;
            }
            File::move($tmp, $p['destino']);
            if (! $mismoLugar) {
                File::delete($p['ruta']); // el original ya está respaldado
                $mapa['img/'.$p['rel']] = 'img/'.$p['destRel'];
            }
            $despues += $r['bytes'];
            $hechos++;
            $this->line('  ✓ '.$p['rel'].'  '.$this->kb($p['bytes']).' → '.$this->kb($r['bytes']));
        }

        if ($mapa) {
            $this->actualizarBd($mapa);
        }

        $this->newLine();
        $this->info("Listo: $hechos imagen(es) optimizadas. Peso total: ".$this->kb($antes).' → '.$this->kb($despues).' (ahorro '.$this->kb(max(0, $antes - $despues)).').');
        $this->line('Copia de los originales: '.$respaldo.' (fuera de public y fuera de git).');
        $this->line('Siguiente paso: abre el sitio, revisa que todo se vea bien y sube los cambios a GitHub.');
        $this->videoAyuda($planes);

        return self::SUCCESS;
    }

    private function kb(int|float $b): string
    {
        return $b >= 1048576 ? round($b / 1048576, 1).' MB' : round($b / 1024).' KB';
    }

    /** Nombre seguro: minúsculas, sin tildes, espacios ni símbolos. */
    private function limpio(string $nombre): string
    {
        $s = Str::slug(mb_scrub($nombre));

        return $s !== '' ? $s : 'imagen';
    }

    private function nombreDanado(string $n): bool
    {
        return ! mb_check_encoding($n, 'UTF-8') || (bool) preg_match('/[\x{FFFD}\x{2502}\x{2524}\x{2593}\x{251C}]|Ã|├|┬/u', $n);
    }

    /** Todo el texto del proyecto donde se podría nombrar una imagen. */
    private function codigoFuente(): string
    {
        $txt = '';
        foreach (['resources/views', 'app', 'public/css', 'public/js', 'lang', 'config', 'routes', 'database'] as $d) {
            $ruta = base_path($d);
            if (! is_dir($ruta)) {
                continue;
            }
            foreach (File::allFiles($ruta) as $f) {
                if (in_array(strtolower($f->getExtension()), ['php', 'js', 'css'], true) && $f->getSize() < 500000) {
                    $txt .= "\n".strtolower((string) file_get_contents($f->getPathname()));
                }
            }
        }

        return $txt;
    }

    private function seUsa(string $codigo, string $nombre): bool
    {
        return (bool) preg_match('~[\'"/]'.preg_quote(strtolower($nombre), '~').'[\'".]~', $codigo);
    }

    /** Cambia en la base de datos las rutas de imágenes que se renombraron. */
    private function actualizarBd(array $mapa): void
    {
        try {
            $n = 0;
            foreach (Tour::all() as $t) {
                $c = [];
                if (isset($mapa[$t->imagen])) {
                    $c['imagen'] = $mapa[$t->imagen];
                }
                if (is_array($t->imagenes)) {
                    $nuevo = array_map(fn ($r) => $mapa[$r] ?? $r, $t->imagenes);
                    if ($nuevo !== $t->imagenes) {
                        $c['imagenes'] = $nuevo;
                    }
                }
                if ($c) {
                    $t->update($c);
                    $n++;
                }
            }
            foreach (TourItinerario::all() as $i) {
                if (is_array($i->imagenes)) {
                    $nuevo = array_map(fn ($r) => $mapa[$r] ?? $r, $i->imagenes);
                    if ($nuevo !== $i->imagenes) {
                        $i->update(['imagenes' => $nuevo]);
                        $n++;
                    }
                }
            }
            $this->info("Base de datos: $n registro(s) actualizados con los nombres nuevos.");
        } catch (\Throwable $e) {
            $this->warn('No se pudo actualizar la base de datos ('.$e->getMessage().'). Si tus tours muestran imágenes rotas, ejecuta: php artisan tours:imagenes');
        }
    }

    private function videoAyuda(array $planes): void
    {
        foreach ($planes as $p) {
            if (in_array($p['ext'], ['mp4', 'webm'], true) && $p['bytes'] > 8 * 1048576) {
                $this->newLine();
                $this->warn('El video '.$p['rel'].' pesa '.$this->kb($p['bytes']).'. Para el fondo del inicio basta algo así (requiere ffmpeg):');
                $this->line('  ffmpeg -i "public/img/'.$p['rel'].'" -vf scale=-2:720 -an -crf 28 -preset slow -movflags +faststart public/img/hero-video.mp4');
            }
        }
    }
}
