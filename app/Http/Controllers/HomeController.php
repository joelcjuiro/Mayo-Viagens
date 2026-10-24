<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use App\Models\Tour;

class HomeController extends Controller
{
    public function index()
    {
        $tours = Tour::publicos()
            ->latest()
            ->take(6)
            ->get();

        // Secciones del inicio (Tour místico, Tour clásico, Machu Picchu...) con sus tours publicados.
        // Si la migración aún no se corrió, el inicio sigue funcionando sin secciones.
        $secciones = rescue(fn () => Seccion::where('activo', true)
            ->orderBy('orden')
            ->with(['toursPublicos' => fn ($q) => $q->latest()])
            ->get()
            ->filter(fn ($s) => $s->toursPublicos->isNotEmpty())
            ->values(), collect(), false);

        // Datos del catálogo para categorías y destinos
        $catalogo = Tour::publicos()->select(['id', 'nombre', 'slug', 'categoria', 'destino', 'imagen'])->latest()->get();

        $categorias = $catalogo->filter(fn ($t) => filled($t->categoria))
            ->groupBy(fn ($t) => trim($t->categoria))
            ->map(fn ($g, $k) => ['nombre' => $k, 'total' => $g->count(), 'imagen' => $g->first(fn ($t) => $t->imagen)?->imagen_url])
            ->sortByDesc('total')->values()->take(6);

        $destinos = collect(['Cusco', 'Puno', 'Arequipa', 'Lima'])
            ->mapWithKeys(fn ($d) => [mb_strtolower($d) => ['nombre' => $d, 'total' => 0, 'imagen' => null]]);
        foreach ($catalogo->filter(fn ($t) => filled($t->destino)) as $t) {
            $k = mb_strtolower(trim($t->destino));
            $d = $destinos->get($k, ['nombre' => trim($t->destino), 'total' => 0, 'imagen' => null]);
            $d['total']++;
            $d['imagen'] = $d['imagen'] ?: $t->imagen_url;
            $destinos->put($k, $d);
        }
        $destinos = $destinos->sortByDesc('total')->values()->take(8);

        // Experiencia destacada (requiere la migración nueva; si aún no se corrió, se omite)
        $destacado = rescue(fn () => Tour::publicos()->where('destacado', true)->latest()->first(), null, false);

        return view('inicio', compact('tours', 'secciones', 'categorias', 'destinos', 'destacado'));
    }

    public function nosotros()
    {
        return view('nosotros');
    }
}
