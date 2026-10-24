<?php

namespace App\Http\Controllers;

use App\Models\Reclamo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReclamoController extends Controller
{
    public function create()
    {
        return view('reclamaciones');
    }

    public function store(Request $request)
    {
        // Campo trampa para robots: las personas no lo ven ni lo llenan
        if (filled($request->input('web'))) {
            return redirect()->route('reclamaciones');
        }

        $datos = $request->validate([
            'tipo' => ['required', 'in:reclamo,queja'],
            'nombre' => ['required', 'string', 'max:150'],
            'documento_tipo' => ['required', 'in:DNI,CE,Pasaporte,Otro'],
            'documento_numero' => ['required', 'string', 'max:30'],
            'domicilio' => ['required', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150'],
            'menor_edad' => ['nullable', 'boolean'],
            'apoderado' => ['nullable', 'required_if:menor_edad,1', 'string', 'max:150'],
            'bien' => ['required', 'in:producto,servicio'],
            'monto' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'descripcion_bien' => ['required', 'string', 'max:1000'],
            'detalle' => ['required', 'string', 'max:3000'],
            'pedido' => ['required', 'string', 'max:3000'],
            'acepta' => ['accepted'],
        ], [
            'apoderado.required_if' => 'Indica el nombre del padre, madre o tutor si eres menor de edad.',
            'acepta.accepted' => 'Debes confirmar que la información es verdadera.',
        ]);

        unset($datos['acepta']);
        $datos['menor_edad'] = $request->boolean('menor_edad');
        $datos['ip'] = $request->ip();

        $reclamo = Reclamo::create($datos + ['codigo' => 'TMP-'.Str::random(10)]);
        $reclamo->update(['codigo' => sprintf('LR-%s-%05d', now()->year, $reclamo->id)]);

        return redirect()->route('reclamaciones')->with('codigo', $reclamo->codigo);
    }
}
