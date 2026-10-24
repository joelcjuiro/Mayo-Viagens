<?php

namespace App\Support;

use App\Models\Reserva;
use App\Models\Tour;
use Illuminate\Support\Facades\Cache;

/**
 * Cifras públicas del sitio. Solo muestra datos reales: tours activos, viajeros de
 * reservas confirmadas (o el valor de config/mayo.php) y lo que definas en la config.
 */
class SiteStats
{
    public static function get(): array
    {
        return rescue(fn () => Cache::remember('mayo.site-stats', 600, function () {
            $travelers = config('mayo.travelers') !== null
                ? (int) config('mayo.travelers')
                : (int) Reserva::where('estado', 'confirmada')->sum('personas');

            $since = (int) config('mayo.since');

            return [
                'tours' => Tour::publicos()->count(),
                'travelers' => $travelers,
                'years' => $since > 1990 ? max(1, now()->year - $since) : null,
                'rating' => config('mayo.rating') !== null ? (float) config('mayo.rating') : null,
                'rating_count' => config('mayo.rating_count') !== null ? (int) config('mayo.rating_count') : null,
            ];
        }), ['tours' => 0, 'travelers' => 0, 'years' => null, 'rating' => null, 'rating_count' => null], false);
    }

    /** Formatea 1234 -> 1.2k+, 87 -> 87+ */
    public static function short(int $n): string
    {
        return ($n >= 1000 ? rtrim(rtrim(number_format($n / 1000, 1), '0'), '.').'k' : $n).'+';
    }
}
