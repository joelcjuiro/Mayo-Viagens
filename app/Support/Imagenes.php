<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Optimizador de imágenes (GD). Sirve para que una foto pesada que sube un pasajero
 * (o el equipo desde el panel) nunca llegue pesada a la página:
 *   - la reduce a un máximo de píxeles,
 *   - corrige la orientación del celular (EXIF) y borra los metadatos,
 *   - la guarda en WebP (o JPG si el servidor no soporta WebP),
 *   - y genera una miniatura para las listas.
 */
class Imagenes
{
    /** Límite de píxeles que aceptamos decodificar (evita "bombas" que agotan la memoria). */
    public const MAX_PIXELES = 40_000_000;

    public static function disponible(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }

    public static function soportaWebp(): bool
    {
        return self::disponible() && function_exists('imagewebp') && ! empty(gd_info()['WebP Support']);
    }

    /** 'webp' | 'jpeg' según lo pedido y lo que soporte el servidor. */
    public static function formato(string $pedido = 'auto'): string
    {
        return ($pedido === 'jpeg' || ! self::soportaWebp()) ? 'jpeg' : 'webp';
    }

    public static function extension(string $formato): string
    {
        return $formato === 'webp' ? 'webp' : 'jpg';
    }

    /** Lee una imagen (JPG/PNG/WebP) a memoria, ya con la orientación correcta. */
    public static function cargar(string $ruta): ?GdImage
    {
        $info = @getimagesize($ruta);
        if (! $info) {
            return null;
        }
        [$ancho, $alto, $tipo] = $info;
        if ($ancho < 1 || $alto < 1 || $ancho * $alto > self::MAX_PIXELES) {
            return null;
        }

        @ini_set('memory_limit', '512M');

        $img = match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
            IMAGETYPE_PNG => @imagecreatefrompng($ruta),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($ruta) : false,
            default => false,
        };
        if (! $img) {
            return null;
        }

        if (! imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }

        if ($tipo === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($ruta);
            $giro = match ((int) ($exif['Orientation'] ?? 1)) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };
            if ($giro !== 0) {
                $rotada = imagerotate($img, $giro, 0);
                if ($rotada) {
                    $img = $rotada;
                }
            }
        }

        return $img;
    }

    /** Reduce para que el lado más largo sea $lado como máximo (nunca agranda). */
    public static function escalar(GdImage $img, int $lado): GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $r = min(1, $lado / max($w, $h));
        if ($r >= 1) {
            return $img;
        }
        $nw = max(1, (int) round($w * $r));
        $nh = max(1, (int) round($h * $r));

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    /**
     * Guarda la imagen. Si $maxBytes está definido, baja la calidad (y luego el tamaño)
     * hasta que el archivo quepa. Devuelve el peso final en bytes.
     */
    public static function guardar(GdImage $img, string $destino, string $formato, int $calidad = 80, ?int $maxBytes = null): int
    {
        File::ensureDirectoryExists(dirname($destino));
        $q = $calidad;
        $actual = $img;

        for ($i = 0; $i < 8; $i++) {
            self::escribir($actual, $destino, $formato, $q);
            clearstatcache(true, $destino);
            $bytes = (int) @filesize($destino);
            if (! $maxBytes || $bytes <= $maxBytes) {
                return $bytes;
            }
            if ($q > 55) {
                $q -= 10;

                continue;
            }
            $actual = self::escalar($actual, (int) round(max(imagesx($actual), imagesy($actual)) * 0.85));
        }

        clearstatcache(true, $destino);

        return (int) @filesize($destino);
    }

    private static function escribir(GdImage $img, string $destino, string $formato, int $q): void
    {
        if ($formato === 'webp') {
            imagewebp($img, $destino, $q);

            return;
        }
        // JPG no tiene transparencia: se pone sobre fondo blanco
        $plano = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagefill($plano, 0, 0, imagecolorallocate($plano, 255, 255, 255));
        imagecopy($plano, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        imageinterlace($plano, true);
        imagejpeg($plano, $destino, $q);
    }

    /**
     * Convierte un archivo del disco (lo usa el comando mayo:imagenes).
     * Devuelve ['ancho', 'alto', 'bytes'] o null si no se pudo leer.
     */
    public static function convertir(string $origen, string $destino, int $lado, int $calidad, ?int $maxBytes, string $formato): ?array
    {
        if (! self::disponible()) {
            return null;
        }
        $img = self::cargar($origen);
        if (! $img) {
            return null;
        }
        $img = self::escalar($img, $lado);
        $bytes = self::guardar($img, $destino, self::formato($formato), $calidad, $maxBytes);

        return ['ancho' => imagesx($img), 'alto' => imagesy($img), 'bytes' => $bytes];
    }

    /**
     * Guarda una foto subida por un visitante o por el panel.
     *
     * @param  array{lado?:int,calidad?:int,max_bytes?:int,miniatura?:int|null}  $op
     * @return array{ruta:string,miniatura:?string,bytes:int}|null  null si el archivo no es una imagen válida
     */
    public static function guardarSubida(UploadedFile $archivo, string $carpeta, array $op = []): ?array
    {
        $lado = $op['lado'] ?? 1600;
        $calidad = $op['calidad'] ?? 80;
        $maxBytes = $op['max_bytes'] ?? 350 * 1024;
        $mini = array_key_exists('miniatura', $op) ? $op['miniatura'] : 480;

        $dir = public_path($carpeta);
        File::ensureDirectoryExists($dir);
        $base = Str::random(24);

        // Sin la extensión GD solo aceptamos archivos que ya vengan livianos
        if (! self::disponible()) {
            if (($archivo->getSize() ?: 0) > 1024 * 1024) {
                return null;
            }
            $ext = strtolower($archivo->guessExtension() ?: 'jpg');
            $ext = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ext : 'jpg';
            $archivo->move($dir, "$base.$ext");

            return ['ruta' => "$carpeta/$base.$ext", 'miniatura' => null, 'bytes' => (int) @filesize("$dir/$base.$ext")];
        }

        $img = self::cargar((string) $archivo->getRealPath());
        if (! $img) {
            return null;
        }

        $formato = self::formato('auto');
        $ext = self::extension($formato);

        $grande = self::escalar($img, $lado);
        $bytes = self::guardar($grande, "$dir/$base.$ext", $formato, $calidad, $maxBytes);

        $rutaMini = null;
        if ($mini) {
            $chica = self::escalar($grande, $mini);
            self::guardar($chica, "$dir/$base-m.$ext", $formato, 72, 90 * 1024);
            $rutaMini = "$carpeta/$base-m.$ext";
        }

        return ['ruta' => "$carpeta/$base.$ext", 'miniatura' => $rutaMini, 'bytes' => $bytes];
    }

    /** Ruta de la miniatura de una foto guardada con guardarSubida() (o la misma foto si no hay). */
    public static function miniaturaDe(?string $ruta): ?string
    {
        if (! $ruta) {
            return null;
        }
        $mini = preg_replace('/\.(webp|jpe?g|png)$/i', '-m.$1', $ruta);
        if ($mini && $mini !== $ruta && is_file(public_path($mini))) {
            return $mini;
        }

        return $ruta;
    }

    /** Borra una foto subida y su miniatura. Solo actúa dentro de public/uploads. */
    public static function borrarSubida(?string $ruta): void
    {
        if (! $ruta || ! str_starts_with($ruta, 'uploads/') || str_contains($ruta, '..')) {
            return;
        }
        File::delete(public_path($ruta));
        $mini = self::miniaturaDe($ruta);
        if ($mini && $mini !== $ruta) {
            File::delete(public_path($mini));
        }
    }
}
