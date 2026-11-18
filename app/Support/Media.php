<?php

namespace App\Support;

/** Ayudas para imágenes y archivos estáticos de public/. */
class Media
{
    /**
     * URL de la primera imagen que exista con ese nombre base, sin importar la extensión.
     * Media::img('hero') -> public/img/hero.webp, hero.jpg, hero.jpeg o hero.png (en ese orden).
     */
    public static function img(string $base, string $dir = 'img', array $exts = ['webp', 'jpg', 'jpeg', 'png']): ?string
    {
        foreach ($exts as $ext) {
            if (is_file(public_path("$dir/$base.$ext"))) {
                return self::v("$dir/$base.$ext");
            }
        }

        return null;
    }

    /** La primera de varias opciones de nombre de archivo que exista dentro de public/$dir. */
    public static function primero(array $archivos, string $dir = 'img/tours'): ?string
    {
        foreach ($archivos as $f) {
            if (is_file(public_path("$dir/$f"))) {
                return asset("$dir/".rawurlencode($f));
            }
        }

        return null;
    }

    /** asset() con ?v=fecha-de-modificación: el navegador cachea fuerte y se actualiza solo al cambiar el archivo. */
    public static function v(string $ruta): string
    {
        $partes = array_map('rawurlencode', explode('/', ltrim($ruta, '/')));
        $url = asset(implode('/', $partes));
        $t = @filemtime(public_path($ruta));

        return $t ? $url.'?v='.$t : $url;
    }
}
