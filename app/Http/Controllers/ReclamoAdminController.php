<?php

namespace App\Http\Controllers;

use App\Models\Reclamo;
use Illuminate\Http\Request;

class ReclamoAdminController extends Controller
{
    public function index(Request $request)
    {
        $estado = in_array($request->query('estado'), ['pendiente', 'atendido'], true) ? $request->query('estado') : null;

        $reclamos = Reclamo::when($estado, fn ($q) => $q->where('estado', $estado))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.reclamos', [
            'reclamos' => $reclamos,
            'estado' => $estado,
            'pendientes' => Reclamo::where('estado', 'pendiente')->count(),
            'total' => Reclamo::count(),
        ]);
    }

    public function show(string $id)
    {
        return view('admin.reclamo', ['r' => Reclamo::findOrFail($id)]);
    }

    public function update(Request $request, string $id)
    {
        $r = Reclamo::findOrFail($id);
        $datos = $request->validate([
            'estado' => ['required', 'in:pendiente,atendido'],
            'respuesta' => ['nullable', 'string', 'max:5000', 'required_if:estado,atendido'],
        ], ['respuesta.required_if' => 'Escribe la respuesta dada al cliente antes de marcarlo como atendido.']);

        $r->update($datos + ['atendido_at' => $datos['estado'] === 'atendido' ? ($r->atendido_at ?? now()) : null]);

        return back()->with('success', 'El reclamo se actualizó.');
    }
}
