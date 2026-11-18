<?php

namespace App\Http\Controllers;

use App\Models\Resena;
use App\Models\Tour;
use App\Support\Imagenes;
use App\Support\Opiniones;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class ResenaController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            Schema::hasTable('resenas') && Schema::hasColumn('resenas', 'fotos'),
            503,
            'Falta actualizar la base de datos. En la terminal del proyecto ejecuta: php artisan migrate'
        );

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
        abort_unless(
            Schema::hasTable('resenas') && Schema::hasColumn('resenas', 'fotos'),
            503,
            'Falta actualizar la base de datos. En la terminal del proyecto ejecuta: php artisan migrate'
        );

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
            'fotos' => ['nullable', 'array', 'max:3'],
            // El navegador ya las reduce; aquí se aceptan hasta 15 MB por si el visitante no tiene JavaScript
            // y el servidor las vuelve a reducir antes de guardarlas.
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'acepta' => ['accepted'],
        ], [
            'fotos.max' => 'Puedes subir hasta 3 fotos.',
            'fotos.*.max' => __('resenas.photo_big'),
            'fotos.*.uploaded' => __('resenas.photo_big'),
            'fotos.*.image' => 'Los archivos deben ser imágenes (JPG, PNG o WebP).',
            'fotos.*.mimes' => 'Las fotos deben ser JPG, PNG o WebP.',
        ]);
        unset($datos['acepta'], $datos['fotos']);

        // Guardar las fotos: se reducen y se convierten a WebP, con miniatura para las listas
        $rutas = [];
        $fallo = false;
        foreach ((array) $request->file('fotos', []) as $foto) {
            if (! $foto || ! $foto->isValid()) {
                continue;
            }
            $g = Imagenes::guardarSubida($foto, 'uploads/opiniones', ['lado' => 1600, 'calidad' => 80, 'max_bytes' => 300 * 1024, 'miniatura' => 480]);
            if ($g) {
                $rutas[] = $g['ruta'];
            } else {
                $fallo = true;
            }
        }
        if ($fallo) {
            array_map([Imagenes::class, 'borrarSubida'], $rutas);

            return back()->withInput()->withErrors(['fotos' => __('resenas.photo_bad')]);
        }

        Resena::create($datos + ['fotos' => $rutas ?: null, 'estado' => 'pendiente', 'ip' => $request->ip()]);

        return redirect()->route('opiniones')->with('enviada', true);
    }
}
