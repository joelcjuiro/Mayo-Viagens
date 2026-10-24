<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Tour;
use Illuminate\Http\Request;

class ReservaController extends Controller
{
    public function create()
    {
        $tours = Tour::publicos()->get();

        return view('reservas', compact('tours'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'tour_id' => ['nullable', 'exists:tours,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['required', 'string', 'max:30'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'personas' => ['required', 'integer', 'min:1', 'max:50'],
            'mensaje' => ['nullable', 'string', 'max:2000'],
        ]);
                $datos = $request->validate([
            // ... tus reglas actuales ...
            'acepta_terminos' => ['accepted'],
        ], [
            'acepta_terminos.accepted' => __('contactpage.accept_error'),
        ]);

        unset($datos['acepta_terminos']);
        $datos['terminos_aceptados_at'] = now();

        Reserva::create($datos);

        return redirect()
            ->route('reservas')
            ->with(
                'success',
                '¡Tu solicitud fue enviada! Nos comunicaremos contigo para confirmar la reserva.'
            );
    }
}