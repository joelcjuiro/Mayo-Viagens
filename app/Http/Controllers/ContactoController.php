<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use Illuminate\Http\Request;

class ContactoController extends Controller
{
    public function create()
    {
        return view('contacto');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'asunto' => ['required', 'string', 'max:150'],
            'mensaje' => ['required', 'string', 'max:3000'],
        ]);

        $datos['leido'] = false;
                $datos = $request->validate([
            // ... tus reglas actuales ...
            'acepta_terminos' => ['accepted'],
        ], [
            'acepta_terminos.accepted' => __('contactpage.accept_error'),
        ]);

        unset($datos['acepta_terminos']);
        $datos['terminos_aceptados_at'] = now();

        Contacto::create($datos);

        return redirect()
            ->route('contacto')
            ->with(
                'success',
                'Gracias por escribirnos. Hemos recibido tu mensaje.'
            );
    }
}
