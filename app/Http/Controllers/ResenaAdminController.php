<?php

namespace App\Http\Controllers;

use App\Models\Resena;
use Illuminate\Http\Request;

class ResenaAdminController extends Controller
{
    public function index(Request $request)
    {
        $estado = in_array($request->query('estado'), ['pendiente', 'aprobada', 'rechazada'], true) ? $request->query('estado') : null;

        return view('admin.resenas', [
            'resenas' => Resena::with('tour:id,nombre')->when($estado, fn ($q) => $q->where('estado', $estado))->latest()->paginate(12)->withQueryString(),
            'estado' => $estado,
            'conteo' => [
                'todas' => Resena::count(),
                'pendiente' => Resena::where('estado', 'pendiente')->count(),
                'aprobada' => Resena::where('estado', 'aprobada')->count(),
                'rechazada' => Resena::where('estado', 'rechazada')->count(),
            ],
        ]);
    }

    public function update(Request $request, string $id)
    {
        $datos = $request->validate([
            'estado' => ['required', 'in:pendiente,aprobada,rechazada'],
            'respuesta' => ['nullable', 'string', 'max:1000'],
        ]);
        Resena::findOrFail($id)->update($datos);
        \Illuminate\Support\Facades\Cache::forget('mayo.site-stats');

        return back()->with('success', 'Opinión actualizada.');
    }

    public function destroy(string $id)
    {
        Resena::findOrFail($id)->delete();

        return back()->with('success', 'Opinión eliminada.');
    }
}
