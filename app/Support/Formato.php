<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Texto con formato simple escrito en el panel (negritas, cursivas, listas, subtítulos, enlaces) -> HTML seguro.
 *   **negrita**  *cursiva*  ## Subtítulo  - lista  1. lista  > aviso destacado  [texto](https://enlace)
 * El HTML pegado se elimina. Los saltos de línea se respetan (como antes, con nl2br), así que los textos
 * antiguos sin formato se siguen viendo igual.
 */
class Formato
{
    public static function html(?string $texto): string
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return '';
        }

        $html = Str::markdown($texto, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'renderer' => ['soft_break' => "<br>\n"],
        ]);

        // Enlaces externos en pestaña nueva
        return (string) preg_replace_callback('/<a href="(https?:\/\/[^"]+)"/i', function ($m) {
            $host = parse_url(html_entity_decode($m[1]), PHP_URL_HOST);

            return $host && $host !== request()->getHost()
                ? '<a href="'.$m[1].'" target="_blank" rel="noopener noreferrer"'
                : $m[0];
        }, $html);
    }

    /** Texto plano (sin símbolos de formato) para resúmenes y meta descripciones. */
    public static function plano(?string $texto): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags(Str::markdown((string) $texto, ['html_input' => 'strip']))));
    }
}
