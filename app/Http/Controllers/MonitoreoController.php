<?php

namespace App\Http\Controllers;

use App\Support\Google;
use App\Support\Salud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Panel "Monitoreo y Google": salud del sitio, visitas (GA4) y velocidad (PageSpeed). */
class MonitoreoController extends Controller
{
    public function index(Request $request)
    {
        $dias = in_array((int) $request->query('dias'), [7, 28, 90], true) ? (int) $request->query('dias') : 28;
        $salud = Salud::revisar();

        return view('admin.monitoreo', [
            'dias' => $dias,
            'ga' => Google::reporte($dias),
            'psi' => Google::pagespeedEnCache('mobile'),
            'psiLocal' => str_contains(url('/'), 'localhost') || str_contains(url('/'), '127.0.0.1'),
            'salud' => $salud,
            'puntaje' => Salud::puntaje($salud),
            'checklist' => Google::checklist(),
        ]);
    }

    public function pagespeed()
    {
        $r = Google::pagespeed('mobile', true);

        return redirect()->route('admin.monitoreo')->with(
            $r['estado'] === 'ok' ? 'success' : 'error',
            $r['estado'] === 'ok' ? 'Medición de PageSpeed actualizada.' : ($r['mensaje'] ?? 'No se pudo medir.')
        );
    }

    public function actualizar()
    {
        foreach ([7, 28, 90] as $d) {
            Cache::forget("mayo.ga.$d");
        }
        Cache::forget('mayo.google.token');

        return redirect()->route('admin.monitoreo')->with('success', 'Datos de Google actualizados.');
    }
}
