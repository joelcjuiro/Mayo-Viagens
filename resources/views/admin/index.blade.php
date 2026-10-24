@extends('layouts.admin')
@section('title', 'Resumen')
@section('heading', 'Hola, '.(auth()->user()->name ?? 'Admin'))
@section('subheading', 'Así va tu agencia: ventas, atención al cliente y confianza del sitio')

@section('content')
@php
    $usd = fn ($n) => 'US$ '.number_format((float) $n, 0, '.', ',');
    $rangos = [7 => '7 días', 30 => '30 días', 90 => '90 días', 365 => '12 meses'];
    $maxReservas = max(1, collect($meses)->max('reservas'));
    $maxIngresos = max(1, collect($meses)->max('ingresos'));
    $maxTour = max(1, $topTours->max('reservas_count') ?? 1);

    $nPend = (int) ($porEstado['pendiente'] ?? 0);
    $nConf = (int) ($porEstado['confirmada'] ?? 0);
    $nCanc = (int) ($porEstado['cancelada'] ?? 0);
    $tot = max(1, $nPend + $nConf + $nCanc);
    $p1 = round($nConf / $tot * 100, 1);
    $p2 = round(($nConf + $nPend) / $tot * 100, 1);
    $donut = "--a: {$p1}%; --b: {$p2}%";

    $badge = function ($d) {
        if ($d === null) return ['muted', 'sin datos previos'];
        return [$d >= 0 ? 'ok' : 'bad', ($d >= 0 ? '▲ ' : '▼ ').abs($d).'% vs. periodo anterior'];
    };
    $horas = $kpis['horas_respuesta'];
    $horasTxt = $horas === null ? '—' : ($horas < 1 ? round($horas * 60).' min' : $horas.' h');
    $totalAlertas = $alertas['pendientes_viejas'] + $alertas['salidas_sin_confirmar'] + $alertas['mensajes_viejos'];
    $tags = ['pendiente' => 'warn', 'confirmada' => 'ok', 'cancelada' => 'bad', 'nuevo' => 'warn', 'leido' => 'muted'];
@endphp

{{-- Hero --}}
<section class="hero-dash reveal">
    <div>
        <p class="eyebrow">Panel de Mayo Viagens</p>
        <h2>Tus tours, <em>listos para vender</em></h2>
        <p>Crea un tour, guárdalo como borrador y publícalo cuando esté completo.</p>
        <a class="hero-cta" href="{{ route('admin.tours.create') }}">Crear un tour <span>→</span></a>
    </div>
    <div class="hero-stats">
        <div><b>{{ $estadoTours['publicados'] }}</b><span>Publicados</span></div>
        <div><b>{{ $estadoTours['borradores'] }}</b><span>Borradores</span></div>
        <div><b>{{ $pendientes }}</b><span>Reservas pendientes</span></div>
    </div>
</section>

{{-- Barra de herramientas --}}
<section class="toolbar">
    <nav class="range" aria-label="Periodo">
        @foreach($rangos as $d => $label)
            <a href="{{ route('admin.index', ['rango' => $d]) }}" @class(['on' => $rango === $d])>{{ $label }}</a>
        @endforeach
    </nav>
    <div class="quick">
        <a class="btn-admin" href="{{ route('admin.tours.create') }}">+ Nuevo tour</a>
        <a class="btn-ghost" href="{{ route('admin.secciones') }}">Secciones del inicio</a>
        <a class="btn-ghost" href="{{ route('admin.reservas', ['estado' => 'pendiente']) }}">Atender pendientes ({{ $pendientes }})</a>
        <a class="btn-ghost" href="{{ route('admin.reservas.export') }}">⬇ Exportar reservas (CSV)</a>
    </div>
</section>

{{-- KPIs --}}
<section class="kpi-grid">
    @php [$c1, $t1] = $badge($kpis['reservas_delta']); [$c2, $t2] = $badge($kpis['ingresos_delta']); [$c3, $t3] = $badge($kpis['viajeros_delta']); @endphp
    <article class="kpi"><p>Reservas recibidas</p><strong>{{ $kpis['reservas'] }}</strong><span class="tag {{ $c1 }}">{{ $t1 }}</span></article>
    <article class="kpi"><p>Ingresos estimados</p><strong>{{ $usd($kpis['ingresos']) }}</strong><span class="tag {{ $c2 }}">{{ $t2 }}</span><small>Reservas confirmadas × precio por persona</small></article>
    <article class="kpi"><p>Viajeros confirmados</p><strong>{{ $kpis['viajeros'] }}</strong><span class="tag {{ $c3 }}">{{ $t3 }}</span></article>
    <article class="kpi"><p>Tasa de confirmación</p><strong>{{ $kpis['tasa'] === null ? '—' : $kpis['tasa'].'%' }}</strong><small>Confirmadas sobre recibidas en el periodo</small></article>
    <article class="kpi"><p>Ticket promedio</p><strong>{{ $kpis['ticket'] === null ? '—' : $usd($kpis['ticket']) }}</strong><small>Por reserva confirmada</small></article>
    <article class="kpi"><p>Tiempo de atención</p><strong>{{ $horasTxt }}</strong><small>Aprox.: desde que llega la reserva hasta que cambias su estado</small></article>
</section>

{{-- Atención + estado --}}
<section class="grid-3">
    <article class="panel">
        <header><h2>Requiere tu atención</h2><span class="tag {{ $totalAlertas ? 'bad' : 'ok' }}">{{ $totalAlertas ? $totalAlertas.' pendiente(s)' : 'Todo al día' }}</span></header>
        <ul class="todo">
            <li @class(['done' => $alertas['pendientes_viejas'] === 0])><b>{{ $alertas['pendientes_viejas'] }}</b> reserva(s) pendientes hace más de 24 h <a href="{{ route('admin.reservas', ['estado' => 'pendiente']) }}">Ver</a></li>
            <li @class(['done' => $alertas['salidas_sin_confirmar'] === 0])><b>{{ $alertas['salidas_sin_confirmar'] }}</b> salida(s) en los próximos 7 días sin confirmar <a href="{{ route('admin.reservas', ['estado' => 'pendiente']) }}">Ver</a></li>
            <li @class(['done' => $alertas['mensajes_viejos'] === 0])><b>{{ $alertas['mensajes_viejos'] }}</b> mensaje(s) sin leer hace más de 24 h <a href="{{ route('admin.contactos') }}">Ver</a></li>
            <li @class(['done' => $mensajesNuevos === 0])><b>{{ $mensajesNuevos }}</b> mensaje(s) nuevos en total <a href="{{ route('admin.contactos') }}">Abrir</a></li>
        </ul>
    </article>

    <article class="panel">
        <header><h2>Estado de las reservas</h2><span class="tag muted">{{ $totalReservas }} en total</span></header>
        <div class="donut-wrap">
            <div class="donut" style="{{ $donut }}" role="img" aria-label="Pendientes {{ $nPend }}, confirmadas {{ $nConf }}, canceladas {{ $nCanc }}"><span>{{ $totalReservas }}</span></div>
            <ul class="legend">
                <li><i class="dot ok"></i>Confirmadas <b>{{ $nConf }}</b></li>
                <li><i class="dot warn"></i>Pendientes <b>{{ $nPend }}</b></li>
                <li><i class="dot bad"></i>Canceladas <b>{{ $nCanc }}</b></li>
            </ul>
        </div>
    </article>

    <article class="panel">
        <header><h2>Próximas salidas</h2><a href="{{ route('admin.reservas') }}">Ver todas</a></header>
        @forelse($proximas as $r)
            @php $dias = (int) today()->diffInDays($r->fecha, false); @endphp
            <div class="dep">
                <div class="dep-date"><b>{{ $r->fecha->format('d') }}</b><small>{{ $r->fecha->locale('es')->translatedFormat('M') }}</small></div>
                <div class="dep-info"><strong>{{ $r->nombre }}</strong><small>{{ $r->tour->nombre ?? 'Sin definir' }} · {{ $r->personas }} pers.</small></div>
                <span class="tag {{ $tags[$r->estado] ?? 'muted' }}">{{ $dias === 0 ? 'Hoy' : ($dias === 1 ? 'Mañana' : 'En '.$dias.' d') }}</span>
            </div>
        @empty
            <p class="empty">No hay salidas programadas.</p>
        @endforelse
    </article>
</section>

{{-- Gráfico + top tours --}}
<section class="grid-2">
    <article class="panel">
        <header><h2>Reservas e ingresos por mes</h2><span class="tag muted">últimos 6 meses</span></header>
        <div class="bars" role="img" aria-label="Reservas por mes de los últimos 6 meses">
            @foreach($meses as $m)
                <div class="bar-col">
                    <span class="bar-val">{{ $m['reservas'] }}</span>
                    <div class="bar" style="--h: {{ round($m['reservas'] / $maxReservas * 100) }}%"></div>
                    <span class="bar-lbl">{{ $m['label'] }}</span>
                    <span class="bar-sub">{{ $usd($m['ingresos']) }}</span>
                </div>
            @endforeach
        </div>
    </article>

    <article class="panel">
        <header><h2>Tours más reservados</h2><span class="tag muted">{{ $toursActivos }} activos de {{ $totalTours }}</span></header>
        @forelse($topTours as $t)
            <div class="rank rank-2">
                <span>{{ $t->nombre }}<small>{{ $usd($t->ingresos) }} confirmados</small></span>
                <span class="track"><i style="width: {{ round($t->reservas_count / $maxTour * 100) }}%"></i></span>
                <b>{{ $t->reservas_count }}</b>
            </div>
        @empty
            <p class="empty">Aún no hay tours.</p>
        @endforelse
    </article>
</section>

{{-- Catálogo: borradores + secciones del inicio --}}
<section class="grid-2 even">
    <article class="panel">
        <header><h2>Borradores en curso</h2><a href="{{ route('admin.tours', ['estado' => 'borrador']) }}">Ver todos</a></header>
        <div class="stat-row">
            <span class="tag ok">{{ $estadoTours['publicados'] }} publicados</span>
            <span class="tag warn">{{ $estadoTours['borradores'] }} borradores</span>
            <span class="tag muted">{{ $estadoTours['ocultos'] }} ocultos</span>
        </div>
        @forelse($borradores as $b)
            <div class="dep">
                <div class="dep-info"><strong>{{ $b->nombre }}</strong><small>Editado {{ $b->updated_at->locale('es')->diffForHumans() }}@if($faltan = \App\Http\Controllers\TourAdminController::faltantes($b)) · falta: {{ implode(', ', $faltan) }}@else · listo para publicar @endif</small></div>
                <a class="link-btn" href="{{ route('admin.tours.edit', $b->id) }}">Continuar</a>
            </div>
        @empty
            <p class="empty">No tienes borradores. Usa «Guardar borrador» en el editor para seguir luego.</p>
        @endforelse
    </article>

    <article class="panel">
        <header><h2>Secciones del inicio</h2><a href="{{ route('admin.secciones') }}">Administrar</a></header>
        @if($sinSeccion > 0)<p class="hint">{{ $sinSeccion }} tour(es) publicados no están en ninguna sección del inicio.</p>@endif
        @forelse($seccionesResumen as $sec)
            <div class="rank rank-2">
                <span>{{ $sec->nombre }}<small>{{ $sec->activo ? ($sec->publicos_count ? 'Visible en el inicio' : 'Oculta: sin tours publicados') : 'Desactivada' }}</small></span>
                <span class="track"><i style="width: {{ $sec->publicos_count ? min(100, $sec->publicos_count * 25) : 0 }}%"></i></span>
                <b>{{ $sec->publicos_count }}</b>
            </div>
        @empty
            <p class="empty">Aún no hay secciones.</p>
        @endforelse
    </article>
</section>

{{-- Confianza + actividad --}}
<section class="grid-2 even">
    <article class="panel">
        <header><h2>Confianza del cliente</h2><span class="tag {{ $confianzaScore >= 80 ? 'ok' : ($confianzaScore >= 50 ? 'warn' : 'bad') }}">{{ $confianzaScore }}% completo</span></header>
        <p class="hint">Lo que un viajero revisa antes de pagar a una agencia que no conoce. Completa lo que falte.</p>
        <span class="track big"><i style="width: {{ $confianzaScore }}%"></i></span>
        <ul class="checks">
            @foreach($confianza as [$label, $ok, $hint])
                <li @class(['ok' => $ok])><span class="ck" aria-hidden="true">{{ $ok ? '✓' : '!' }}</span><span>{{ $label }}@unless($ok)<small>{{ $hint }}</small>@endunless</span></li>
            @endforeach
        </ul>
    </article>

    <article class="panel">
        <header><h2>Actividad reciente</h2></header>
        @forelse($actividad as $a)
            <div class="act">
                <span class="act-ico {{ $a['tipo'] }}" aria-hidden="true">{{ $a['tipo'] === 'reserva' ? '📅' : '✉' }}</span>
                <div><strong>{{ $a['titulo'] }}</strong><small>{{ $a['tipo'] === 'reserva' ? 'Nueva reserva' : 'Mensaje' }} · {{ $a['detalle'] }}</small></div>
                <span class="act-time">{{ $a['fecha']->locale('es')->diffForHumans(['short' => true]) }}</span>
            </div>
        @empty
            <p class="empty">Todavía no hay actividad.</p>
        @endforelse
    </article>
</section>

<article class="panel">
    <header>
        <h2>Últimas reservas</h2>
        <a href="{{ route('admin.reservas') }}">Ver todas</a>
    </header>
    @include('admin.partials.reservas-table', ['reservas' => $ultimasReservas, 'editable' => false])
</article>
@endsection
