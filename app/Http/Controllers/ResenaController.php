<?php

namespace App\Http\Controllers;

use App\Models\Resena;
use App\Models\Tour;
use App\Support\Opiniones;
use Illuminate\Http\Request;

class ResenaController extends Controller
{
    public function index(Request $request)
    {
        $resenas = Resena::aprobadas()->with('tour:id,nombre,slug')->latest()->paginate(9);
        $tours = Tour::publicos()->select(['id', 'nombre', 'slug'])->orderBy('nombre')->get();
        $preseleccion = $tours->firstWhere('slug', $request->query('tour'))?->id;

        return view('opiniones', [
            'resenas' => $resenas,
            'tours' => $tours,
            'preseleccion' => old('tour_id', $preseleccion),
            'resumen' => Opiniones::resumen(0),
            'distribucion' => Opiniones::distribucion(),
        ]);
    }

    public function store(Request $request)
    {
        if (filled($request->input('web'))) { // campo trampa para robots
            return redirect()->route('opiniones');
        }

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'pais' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'tour_id' => ['nullable', 'integer', 'exists:tours,id'],
            'calificacion' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['required', 'string', 'min:15', 'max:1500'],
            'acepta' => ['accepted'],
        ]);
        unset($datos['acepta']);

        Resena::create($datos + ['estado' => 'pendiente', 'ip' => $request->ip()]);

        return redirect()->route('opiniones')->with('enviada', true);
    }
}
