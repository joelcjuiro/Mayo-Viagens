<?php

namespace App\Http\Controllers;

use App\Support\Traductor;
use Illuminate\Http\Request;

class TraduccionController extends Controller
{
    public function traducir(Request $request)
    {
        $datos = $request->validate([
            'source' => ['required', 'in:pt,es,en'],
            'items' => ['required', 'array', 'min:1', 'max:300'],
            'items.*.id' => ['required', 'string', 'max:80'],
            'items.*.text' => ['required', 'string', 'max:10000'],
        ]);

        $textos = [];
        foreach ($datos['items'] as $it) {
            $textos[$it['id']] = $it['text'];
        }

        try {
            $destinos = array_values(array_diff(Traductor::IDIOMAS, [$datos['source']]));

            return response()->json(['ok' => true, 'traducciones' => Traductor::traducir($textos, $datos['source'], $destinos)]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
