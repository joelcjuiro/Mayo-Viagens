<?php

namespace App\Support;

use App\Models\Resena;

/** Opiniones aprobadas de los viajeros (para el inicio y la página de opiniones). */
class Opiniones
{
    /** Promedio, total y las últimas reseñas, en el mismo formato que config('mayo.reviews'). */
    public static function resumen(int $limite = 9): array
    {
        return rescue(function () use ($limite) {
            $base = Resena::aprobadas();
            $count = (clone $base)->count();
            if ($count === 0) {
                return ['count' => 0, 'avg' => null, 'items' => []];
            }
            $items = (clone $base)->latest()->take($limite)->get()->map(fn ($r) => [
                'stars' => $r->calificacion,
                'text' => $r->comentario,
                'name' => $r->nombre,
                'country' => $r->pais,
                'date' => $r->created_at->toDateString(),
            ])->all();

            return ['count' => $count, 'avg' => round((float) (clone $base)->avg('calificacion'), 1), 'items' => $items];
        }, ['count' => 0, 'avg' => null, 'items' => []], false);
    }

    /** Cuántas reseñas hay por cada puntuación (5..1). */
    public static function distribucion(): array
    {
        return rescue(function () {
            $filas = Resena::aprobadas()->selectRaw('calificacion, count(*) as n')->groupBy('calificacion')->pluck('n', 'calificacion');
            $out = [];
            foreach ([5, 4, 3, 2, 1] as $s) {
                $out[$s] = (int) ($filas[$s] ?? 0);
            }

            return $out;
        }, [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0], false);
    }
}
