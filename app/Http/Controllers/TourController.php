<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use App\Models\Tour;
use Illuminate\Http\Request;

class TourController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $destinoParam = $request->query('destino');
        $categoriaParam = $request->query('categoria');
        $seccionParam = $request->query('seccion');
        $seccion = $seccionParam ? Seccion::where('slug', $seccionParam)->first() : null;

        $tours = Tour::publicos()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                      ->orWhere('descripcion', 'like', "%{$q}%")
                      ->orWhere('slug', 'like', "%{$q}%")
                      ->orWhere('palabras_clave', 'like', "%{$q}%")
                      ->orWhere('destino', 'like', "%{$q}%")
                      ->orWhere('categoria', 'like', "%{$q}%");
                });
            })
            ->when($destinoParam, fn ($query) => $query->whereRaw('lower(destino) = ?', [mb_strtolower($destinoParam)]))
            ->when($categoriaParam, fn ($query) => $query->where('categoria', $categoriaParam))
            ->when($seccion, fn ($query) => $query->whereHas('secciones', fn ($w) => $w->where('secciones.id', $seccion->id)))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        // Etiqueta para el título de la página
        $destino = $destinoParam ?: $categoriaParam ?: $seccion?->nombre;

        return view('tours', compact('tours', 'q', 'destino'));
    }

    /** Página pública del tour. Se abre por su URL propia (slug); también acepta el ID antiguo. */
    public function show(string $slug)
    {
        $consulta = Tour::publicos()->with(['itinerarioItems', 'precios']);

        $tour = (clone $consulta)->where('slug', $slug)->first()
            ?? (ctype_digit($slug) ? (clone $consulta)->find($slug) : null);

        abort_if(! $tour, 404);

        // Si entraron con el ID antiguo, llevarlos a la URL con nombre.
        if ($tour->slug !== $slug) {
            return redirect()->route('tour.detalle', $tour->slug, 301);
        }

        return view('tour-detalle', compact('tour'));
    }
}