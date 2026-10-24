<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeccionAdminController extends Controller
{
    public function index()
    {
        $secciones = Seccion::orderBy('orden')
            ->withCount(['tours', 'tours as publicos_count' => fn ($q) => $q->where('tours.activo', true)->where('tours.borrador', false)])
            ->get();

        return view('admin.secciones', compact('secciones'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:200'],
        ]);

        $base = Str::slug($datos['nombre']) ?: 'seccion';
        $slug = $base;
        $n = 2;
        while (Seccion::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        Seccion::create($datos + ['slug' => $slug, 'orden' => (int) Seccion::max('orden') + 1, 'activo' => true]);

        return back()->with('success', 'Sección creada. Ya puedes asignarle tours desde el editor.');
    }

    public function update(Request $request, string $id)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'orden' => ['required', 'integer', 'min:0', 'max:999'],
            'tr' => ['nullable', 'array'],
            'tr.*.*' => ['nullable', 'string', 'max:200'],
        ]);
        $datos['traducciones'] = Seccion::limpiarTraducciones($datos['tr'] ?? [], ['nombre', 'descripcion']);
        unset($datos['tr']);
        $datos['activo'] = $request->boolean('activo');

        Seccion::findOrFail($id)->update($datos);

        return back()->with('success', 'Sección actualizada.');
    }

    public function destroy(string $id)
    {
        Seccion::findOrFail($id)->delete(); // los tours no se borran, solo se desvinculan

        return back()->with('success', 'Sección eliminada. Los tours se conservan.');
    }
}
