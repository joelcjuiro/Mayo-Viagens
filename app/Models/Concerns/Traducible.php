<?php

namespace App\Models\Concerns;

/** Campos traducibles guardados en la columna JSON "traducciones" ({"es": {...}, "en": {...}}). */
trait Traducible
{
    /** Devuelve el texto en el idioma actual; si no hay traducción (o es portugués) usa el texto base. */
    public function t(string $campo): ?string
    {
        $locale = app()->getLocale();
        $valor = $this->traducciones[$locale][$campo] ?? null;

        return filled($valor) ? $valor : $this->getAttribute($campo);
    }

    /** Limpia lo que llega del formulario: {"es": {"campo": "texto"}} sin vacíos. */
    public static function limpiarTraducciones($entrada, array $campos): ?array
    {
        $salida = [];
        foreach (['pt', 'es', 'en'] as $lang) {
            foreach ($campos as $campo) {
                $v = trim((string) ($entrada[$lang][$campo] ?? ''));
                if ($v !== '') {
                    $salida[$lang][$campo] = $v;
                }
            }
        }

        return $salida ?: null;
    }
}
