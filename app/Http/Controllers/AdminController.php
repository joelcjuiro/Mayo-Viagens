<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use App\Models\Reserva;
use App\Models\Seccion;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $rango = in_array((int) $request->query('rango'), [7, 30, 90, 365], true) ? (int) $request->query('rango') : 30;
        $desde = now()->subDays($rango)->startOfDay();
        $prevDesde = (clone $desde)->subDays($rango);
        $mesesDesde = now()->subMonths(5)->startOfMonth();

        // Una sola consulta; todo se agrupa en PHP para funcionar en SQLite y MySQL.
        $todas = Reserva::with('tour')->where('created_at', '>=', $prevDesde->lt($mesesDesde) ? $prevDesde : $mesesDesde)->get();

        // Ingreso estimado = precio final del tour x personas (solo reservas confirmadas).
        $valor = fn ($r) => (float) (($r->tour->precio_final ?? 0) * (int) $r->personas);

        $resumen = function (Collection $c) use ($valor) {
            $conf = $c->where('estado', 'confirmada');

            return [
                'reservas' => $c->count(),
                'confirmadas' => $conf->count(),
                'ingresos' => (float) $conf->sum($valor),
                'viajeros' => (int) $conf->sum('personas'),
            ];
        };

        $actual = $resumen($todas->filter(fn ($r) => $r->created_at >= $desde));
        $previo = $resumen($todas->filter(fn ($r) => $r->created_at >= $prevDesde && $r->created_at < $desde));
        $delta = fn ($a, $b) => $b > 0 ? (int) round(($a - $b) / $b * 100) : ($a > 0 ? 100 : null);

        $enRango = $todas->filter(fn ($r) => $r->created_at >= $desde);
        $atendidas = $enRango->where('estado', '!=', 'pendiente');
        $horasRespuesta = $atendidas->isNotEmpty()
            ? round($atendidas->avg(fn ($r) => abs($r->created_at->diffInMinutes($r->updated_at))) / 60, 1)
            : null;

        $kpis = [
            'reservas' => $actual['reservas'],
            'reservas_delta' => $delta($actual['reservas'], $previo['reservas']),
            'ingresos' => $actual['ingresos'],
            'ingresos_delta' => $delta($actual['ingresos'], $previo['ingresos']),
            'viajeros' => $actual['viajeros'],
            'viajeros_delta' => $delta($actual['viajeros'], $previo['viajeros']),
            'tasa' => $actual['reservas'] > 0 ? (int) round($actual['confirmadas'] / $actual['reservas'] * 100) : null,
            'ticket' => $actual['confirmadas'] > 0 ? $actual['ingresos'] / $actual['confirmadas'] : null,
            'horas_respuesta' => $horasRespuesta,
        ];

        // Últimos 6 meses
        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $delMes = $todas->filter(fn ($r) => $r->created_at->format('Y-m') === $m->format('Y-m'));
            $meses[] = [
                'label' => Str::ucfirst($m->locale('es')->translatedFormat('M')),
                'reservas' => $delMes->count(),
                'ingresos' => (float) $delMes->where('estado', 'confirmada')->sum($valor),
            ];
        }

        $porEstado = Reserva::selectRaw('estado, count(*) as n')->groupBy('estado')->pluck('n', 'estado');
        $totalReservas = (int) $porEstado->sum();

        $proximas = Reserva::with('tour')
            ->whereDate('fecha', '>=', today())
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->orderBy('fecha')
            ->take(6)
            ->get();

        $topTours = Tour::with('reservas')->get()->map(function ($t) {
            $t->reservas_count = $t->reservas->count();
            $t->ingresos = (float) $t->reservas->where('estado', 'confirmada')->sum('personas') * (float) $t->precio_final;

            return $t;
        })->sortByDesc('reservas_count')->take(5)->values();

        // Requiere atención
        $pendientes = Reserva::where('estado', 'pendiente')->count();
        $alertas = [
            'pendientes_viejas' => Reserva::where('estado', 'pendiente')->where('created_at', '<', now()->subDay())->count(),
            'salidas_sin_confirmar' => Reserva::where('estado', 'pendiente')->whereBetween('fecha', [today(), today()->addDays(7)])->count(),
            'mensajes_viejos' => Contacto::where('leido', false)->where('created_at', '<', now()->subDay())->count(),
        ];

        // Salud del contenido y de la confianza del sitio
        $toursActivos = Tour::publicos()->get();
        $sinImagen = $toursActivos->filter(fn ($t) => blank($t->imagen))->count();
        $incompletos = $toursActivos->filter(fn ($t) => blank($t->itinerario) || blank($t->incluye) || blank($t->no_incluye))->count();
        $sinTraduccion = $toursActivos->filter(fn ($t) => blank($t->traducciones['es']['nombre'] ?? null) || blank($t->traducciones['en']['nombre'] ?? null))->count();
        $hayActivos = $toursActivos->isNotEmpty();

        $confianza = [
            ['WhatsApp de atención configurado', filled(config('mayo.whatsapp')), 'config/mayo.php → whatsapp'],
            ['Correo de contacto visible en el sitio', filled(config('mayo.email')), 'Define MAYO_EMAIL en .env'],
            ['Tours activos con foto', $hayActivos && $sinImagen === 0, $sinImagen.' tour(es) sin imagen'],
            ['Tours con itinerario, qué incluye y qué no', $hayActivos && $incompletos === 0, $incompletos.' tour(es) incompletos'],
            ['Tours traducidos a español e inglés', $hayActivos && $sinTraduccion === 0, $sinTraduccion.' tour(es) sin traducir'],
            ['Valoración real publicada (con n.º de reseñas)', filled(config('mayo.rating')) && filled(config('mayo.rating_count')), 'MAYO_RATING y MAYO_RATING_COUNT'],
            ['Reseñas reales de clientes cargadas', count(config('mayo.reviews')) > 0, 'config/mayo.php → reviews'],
            ['Redes sociales enlazadas', count(array_filter(config('mayo.social'))) > 0, 'MAYO_INSTAGRAM, MAYO_FACEBOOK…'],
            ['Reservas pendientes atendidas en menos de 24 h', $alertas['pendientes_viejas'] === 0, $alertas['pendientes_viejas'].' esperando más de 24 h'],
            ['Mensajes respondidos en menos de 24 h', $alertas['mensajes_viejos'] === 0, $alertas['mensajes_viejos'].' sin leer hace más de 24 h'],
        ];
        $confianzaScore = (int) round(collect($confianza)->where(1, true)->count() / count($confianza) * 100);

        // Actividad reciente (reservas + mensajes)
        $actividad = Reserva::with('tour')->latest()->take(6)->get()
            ->map(fn ($r) => ['tipo' => 'reserva', 'fecha' => $r->created_at, 'titulo' => $r->nombre, 'detalle' => ($r->tour->nombre ?? 'Sin definir').' · '.$r->personas.' pers.', 'estado' => $r->estado])
            ->concat(Contacto::latest()->take(6)->get()
                ->map(fn ($c) => ['tipo' => 'mensaje', 'fecha' => $c->created_at, 'titulo' => $c->nombre, 'detalle' => $c->asunto ?? 'Sin asunto', 'estado' => $c->leido ? 'leido' : 'nuevo']))
            ->sortByDesc('fecha')->take(8)->values();

        $borradores = Tour::where('borrador', true)->latest('updated_at')->take(5)->get();
        $estadoTours = [
            'publicados' => $toursActivos->count(),
            'borradores' => Tour::where('borrador', true)->count(),
            'ocultos' => Tour::where('borrador', false)->where('activo', false)->count(),
        ];
        $seccionesResumen = Seccion::orderBy('orden')->withCount(['tours as publicos_count' => fn ($q) => $q->where('tours.activo', true)->where('tours.borrador', false)])->get();
        $sinSeccion = Tour::publicos()->whereDoesntHave('secciones')->count();

        return view('admin.index', [
            'borradores' => $borradores,
            'estadoTours' => $estadoTours,
            'seccionesResumen' => $seccionesResumen,
            'sinSeccion' => $sinSeccion,
            'rango' => $rango,
            'kpis' => $kpis,
            'meses' => $meses,
            'porEstado' => $porEstado,
            'totalReservas' => $totalReservas,
            'proximas' => $proximas,
            'topTours' => $topTours,
            'pendientes' => $pendientes,
            'mensajesNuevos' => Contacto::where('leido', false)->count(),
            'totalTours' => Tour::count(),
            'toursActivos' => $toursActivos->count(),
            'alertas' => $alertas,
            'confianza' => $confianza,
            'confianzaScore' => $confianzaScore,
            'actividad' => $actividad,
            'ultimasReservas' => Reserva::with('tour')->latest()->take(5)->get(),
        ]);
    }

    public function exportReservas(Request $request)
    {
        $reservas = Reserva::with('tour')
            ->when($request->query('estado'), fn ($q, $estado) => $q->where('estado', $estado))
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($reservas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel lea bien las tildes
            fputcsv($out, ['ID', 'Recibida', 'Cliente', 'Correo', 'Teléfono', 'Tour', 'Fecha del viaje', 'Personas', 'Estado', 'Mensaje']);
            foreach ($reservas as $r) {
                fputcsv($out, [
                    $r->id, $r->created_at->format('Y-m-d H:i'), $r->nombre, $r->email, $r->telefono,
                    $r->tour->nombre ?? 'Sin definir', $r->fecha->format('Y-m-d'), $r->personas, $r->estado, $r->mensaje,
                ]);
            }
            fclose($out);
        }, 'reservas-mayo-viagens-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function reservas()
    {
        $reservas = Reserva::with('tour')
            ->when(request('estado'), fn ($q, $estado) => $q->where('estado', $estado))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reservas', compact('reservas'));
    }

    public function updateReserva(Request $request, string $id)
    {
        $datos = $request->validate([
            'estado' => ['required', 'in:pendiente,confirmada,cancelada'],
        ]);

        Reserva::findOrFail($id)->update($datos);

        return back()->with('success', 'El estado de la reserva se actualizó.');
    }

    public function contactos()
    {
        $contactos = Contacto::latest()->paginate(15);

        return view('admin.contactos', compact('contactos'));
    }

    public function updateContacto(string $id)
    {
        $contacto = Contacto::findOrFail($id);
        $contacto->update(['leido' => ! $contacto->leido]);

        return back();
    }

    public function mensajes()
    {
        return $this->contactos();
    }
}
