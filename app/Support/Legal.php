<?php

namespace App\Support;

/** Ayudas para las páginas legales (Términos, Privacidad, Código ESNNA). */
class Legal
{
    /** Texto de una clave; si no existe en el idioma actual, usa español (los textos legales están en español). */
    public static function t(string $ns, string $key): mixed
    {
        $full = "$ns.$key";
        $v = trans($full);

        return $v === $full ? trans($full, [], 'es') : $v;
    }

    /** Reemplaza {name}, {ruc}, {address}, {email}, {phone}, {city}. Si un dato está vacío, devuelve null (la línea se omite). */
    public static function fill(?string $text): ?string
    {
        $vars = [
            '{name}' => config('legal.name'),
            '{ruc}' => config('legal.ruc'),
            '{address}' => config('legal.address'),
            '{email}' => config('legal.email'),
            '{phone}' => config('mayo.whatsapp_display'),
            '{city}' => config('legal.city'),
        ];
        foreach ($vars as $k => $v) {
            if ($text !== null && str_contains($text, $k)) {
                if (blank($v)) {
                    return null;
                }
                $text = str_replace($k, $v, $text);
            }
        }

        return $text;
    }

    /** Secciones listas para mostrar: título + párrafos + lista. */
    public static function sections(string $ns): array
    {
        $out = [];
        foreach ((array) self::t($ns, 'sections') as $s) {
            $out[] = [
                't' => self::fill($s['t'] ?? '') ?? '',
                'p' => array_values(array_filter(array_map([self::class, 'fill'], (array) ($s['p'] ?? [])))),
                'l' => array_values(array_filter(array_map([self::class, 'fill'], (array) ($s['l'] ?? [])))),
                'p2' => array_values(array_filter(array_map([self::class, 'fill'], (array) ($s['p2'] ?? [])))),
            ];
        }

        return $out;
    }

    public static function date(): string
    {
        return \Illuminate\Support\Carbon::parse(config('legal.updated'))->locale('es')->translatedFormat('j \d\e F \d\e Y');
    }

    /** Sello/imagen del Código ESNNA: configurado en LEGAL_ESNNA_IMAGE o public/img/esnna.* */
    public static function esnnaImage(): ?string
    {
        $name = config('legal.esnna_image');
        if ($name && file_exists(public_path('img/'.$name))) {
            return asset('img/'.rawurlencode($name));
        }
        $found = glob(public_path('img/esnna.*'));

        return $found ? asset('img/'.rawurlencode(basename($found[0]))) : null;
    }
}
