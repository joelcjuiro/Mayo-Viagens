@extends('layouts.admin')
@section('title', 'Monitoreo y Google')
@section('heading', 'Monitoreo y Google')
@section('subheading', 'Cómo va el sitio: visitas, velocidad, Google y salud del servidor')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-monitoreo.css') }}">@endpush

@section('content')
@php
    $gOk = collect($checklist)->where(2, true)->count();
    $dot = ['ok' => 'ok', 'info' => 'info', 'warn' => 'warn', 'bad' => 'bad'];
    $sc = fn ($n) => $n === null ? 'muted' : ($n >= 90 ? 'ok' : ($n >= 50 ? 'warn' : 'bad'));
    $delta = function ($a, $b) { if (! $b) return null; $d = round(($a - $b) / $b * 100); return ($d >= 0 ? '+' : '').$d.'%'; };
    $psiUrl = 'https://pagespeed.web.dev/analysis?url='.urlencode(url('/'));
@endphp

{{-- ============ RESUMEN ============ --}}
<div class="mo-top">
    <article class="mo-card">
        <h3>Salud del sitio</h3>
        <p class="mo-big {{ $sc($puntaje) }}">{{ $puntaje }}<small>/100</small></p>
        <p class="mo-sub">Servidor, imágenes y seguridad</p>
    </article>
    <article class="mo-card">
        <h3>Google configurado</h3>
        <p class="mo-big {{ $gOk === 4 ? 'ok' : ($gOk >= 2 ? 'warn' : 'bad') }}">{{ $gOk }}<small>/4</small></p>
        <p class="mo-sub">Analytics, Search Console, sitemap y panel</p>
    </article>
    <article class="mo-card">
        <h3>Velocidad en celular</h3>
        @if($psi)<p class="mo-big {{ $sc($psi['rendimiento']) }}">{{ $psi['rendimiento'] }}<small>/100</small></p><p class="mo-sub">PageSpeed · {{ \Carbon\Carbon::parse($psi['hora'])->diffForHumans() }}</p>
        @else<p class="mo-big muted">—</p><p class="mo-sub">Aún sin medir</p>@endif
    </article>
    <article class="mo-card">
        <h3>Visitas ({{ $dias }} días)</h3>
        @if(($ga['estado'] ?? '') === 'ok')<p class="mo-big ok">{{ number_format($ga['actual']['usuarios']) }}</p><p class="mo-sub">{{ $delta($ga['actual']['usuarios'], $ga['previo']['usuarios']) ?? 'visitantes únicos' }} vs. periodo anterior</p>
        @else<p class="mo-big muted">—</p><p class="mo-sub">Conecta Analytics (abajo)</p>@endif
    </article>
</div>

{{-- ============ GOOGLE: CHECKLIST ============ --}}
<article class="panel mo-panel">
    <header><h2>Conexión con Google</h2><span class="tag {{ $gOk === 4 ? 'ok' : 'warn' }}">{{ $gOk }} de 4 listos</span></header>
    <ul class="mo-list">
        @foreach($checklist as [$k, $titulo, $ok, $detalle, $como])
            <li class="{{ $ok ? 'ok' : 'warn' }}">
                <span class="mo-dot" aria-hidden="true">{{ $ok ? '✓' : '!' }}</span>
                <div><strong>{{ $titulo }}</strong><small>{{ $detalle }}</small>@unless($ok)<small class="fix">Cómo: {{ $como }}</small>@endunless</div>
            </li>
        @endforeach
    </ul>
    <p class="mo-links">
        <a href="https://analytics.google.com" target="_blank" rel="noopener">Google Analytics ↗</a>
        <a href="https://search.google.com/search-console" target="_blank" rel="noopener">Search Console ↗</a>
        <a href="{{ $psiUrl }}" target="_blank" rel="noopener">PageSpeed ↗</a>
        <a href="https://search.google.com/test/rich-results?url={{ urlencode(url('/')) }}" target="_blank" rel="noopener">Resultados enriquecidos ↗</a>
        <a href="https://business.google.com" target="_blank" rel="noopener">Perfil de Empresa (Maps) ↗</a>
    </p>
</article>

{{-- ============ ANALYTICS ============ --}}
<article class="panel mo-panel">
    <header>
        <h2>Visitas (Google Analytics)</h2>
        <div class="mo-actions">
            <nav class="range" aria-label="Periodo">@foreach([7, 28, 90] as $d)<a href="{{ route('admin.monitoreo', ['dias' => $d]) }}" @class(['on' => $dias === $d])>{{ $d }} días</a>@endforeach</nav>
            <form method="POST" action="{{ route('admin.monitoreo.actualizar') }}">@csrf<button class="btn-ghost" type="submit">Actualizar</button></form>
        </div>
    </header>

    @if(($ga['estado'] ?? '') === 'ok')
        @php
            $pts = $ga['diario']; $max = max(1, collect($pts)->max('v')); $n = max(1, count($pts) - 1);
            $linea = collect($pts)->map(fn ($p, $i) => round($i / $n * 600, 1).','.round(90 - $p['v'] / $max * 80, 1))->implode(' ');
        @endphp
        <div class="mo-kpis">
            <div><small>Visitantes</small><b>{{ number_format($ga['actual']['usuarios']) }}</b><em>{{ $delta($ga['actual']['usuarios'], $ga['previo']['usuarios']) }}</em></div>
            <div><small>Sesiones</small><b>{{ number_format($ga['actual']['sesiones']) }}</b><em>{{ $delta($ga['actual']['sesiones'], $ga['previo']['sesiones']) }}</em></div>
            <div><small>Páginas vistas</small><b>{{ number_format($ga['actual']['vistas']) }}</b><em>{{ $delta($ga['actual']['vistas'], $ga['previo']['vistas']) }}</em></div>
            <div><small>Interacción</small><b>{{ $ga['actual']['interaccion'] }}%</b><em>sesiones activas</em></div>
            <div><small>Clics a WhatsApp</small><b>{{ number_format($ga['eventos']['whatsapp_click'] ?? 0) }}</b><em>contactos directos</em></div>
            <div><small>Formularios</small><b>{{ number_format(($ga['eventos']['generate_lead'] ?? 0) + ($ga['eventos']['review_submit'] ?? 0)) }}</b><em>reservas, contacto, opiniones</em></div>
        </div>
        @if(count($pts) > 1)
            <svg class="mo-spark" viewBox="0 0 600 96" preserveAspectRatio="none" role="img" aria-label="Visitantes por día"><polyline points="{{ $linea }}" fill="none" stroke="var(--gold)" stroke-width="2.5" vector-effect="non-scaling-stroke"/></svg>
        @endif
        <div class="mo-cols">
            @foreach(['paginas' => 'Páginas más vistas', 'canales' => 'De dónde llegan', 'paises' => 'Países'] as $k => $t)
                <div><h4>{{ $t }}</h4>
                    <ol>@forelse($ga[$k] as $f)<li><span>{{ \Illuminate\Support\Str::limit($f['n'], 34) }}</span><b>{{ number_format($f['v']) }}</b></li>@empty<li><span>Sin datos todavía</span></li>@endforelse</ol></div>
            @endforeach
        </div>
        <small class="mo-foot">Datos de Google · actualizados {{ \Carbon\Carbon::parse($ga['hora'])->diffForHumans() }}. Solo cuentan visitantes que aceptaron las cookies.</small>
    @elseif(($ga['estado'] ?? '') === 'error')
        <div class="mo-msg bad"><strong>Google respondió con un error:</strong> {{ $ga['mensaje'] }}</div>
    @else
        <div class="mo-msg">
            <strong>Todavía no se ven las visitas aquí.</strong>
            <p>El sitio ya puede medir visitas con Google Analytics (basta con poner el ID <code>GOOGLE_ANALYTICS_ID</code> en el <code>.env</code>). Para verlas <em>dentro de este panel</em> falta conectar una cuenta de servicio; la guía paso a paso está en <code>GOOGLE-MONITOREO.md</code>.</p>
        </div>
    @endif
</article>

{{-- ============ PAGESPEED ============ --}}
<article class="panel mo-panel">
    <header><h2>Velocidad real (Google PageSpeed, celular)</h2>
        <form method="POST" action="{{ route('admin.monitoreo.pagespeed') }}" onsubmit="this.querySelector('button').textContent='Midiendo… (hasta 1 min)';this.querySelector('button').disabled=true">@csrf<button class="btn-admin" type="submit" style="border:0;cursor:pointer">Medir ahora</button></form></header>
    @if($psi)
        <div class="mo-scores">
            @foreach(['rendimiento' => 'Rendimiento', 'accesibilidad' => 'Accesibilidad', 'buenas' => 'Buenas prácticas', 'seo' => 'SEO'] as $k => $t)
                <div class="mo-ring {{ $sc($psi[$k]) }}"><b>{{ $psi[$k] ?? '—' }}</b><span>{{ $t }}</span></div>
            @endforeach
        </div>
        <ul class="mo-metrics">
            <li><span>Carga del contenido principal (LCP)</span><b>{{ $psi['lcp'] ?? '—' }}</b></li>
            <li><span>Primera pintura (FCP)</span><b>{{ $psi['fcp'] ?? '—' }}</b></li>
            <li><span>Estabilidad visual (CLS)</span><b>{{ $psi['cls'] ?? '—' }}</b></li>
            <li><span>Tiempo de bloqueo (TBT)</span><b>{{ $psi['tbt'] ?? '—' }}</b></li>
            <li><span>Peso total de la página</span><b>{{ $psi['peso'] ?? '—' }}</b></li>
            @if($psi['real'])<li><span>Experiencia de usuarios reales</span><b>{{ ['FAST' => 'Rápida', 'AVERAGE' => 'Regular', 'SLOW' => 'Lenta'][$psi['real']] ?? $psi['real'] }}</b></li>@endif
        </ul>
        @if($psi['mejoras'])
            <h4>Lo que más mejoraría la velocidad</h4>
            <ul class="mo-metrics">@foreach($psi['mejoras'] as $m)<li><span>{{ $m['t'] }}</span><b>{{ $m['v'] }}</b></li>@endforeach</ul>
        @endif
        <small class="mo-foot">Medido {{ \Carbon\Carbon::parse($psi['hora'])->diffForHumans() }} sobre {{ $psi['url'] }}.</small>
    @elseif($psiLocal)
        <div class="mo-msg"><strong>Disponible cuando publiques el sitio.</strong><p>PageSpeed mide páginas que estén en internet; desde tu computadora (localhost) no puede abrirlas.</p></div>
    @else
        <div class="mo-msg"><p>Aún no hay una medición guardada. Pulsa <strong>Medir ahora</strong>: Google abre tu página como lo haría un celular y te da las notas.</p></div>
    @endif
</article>

{{-- ============ SALUD ============ --}}
@foreach(['sistema' => 'Salud del servidor', 'rendimiento' => 'Imágenes y rendimiento'] as $g => $titulo)
<article class="panel mo-panel">
    <header><h2>{{ $titulo }}</h2></header>
    <ul class="mo-list">
        @foreach($salud[$g] ?? [] as $c)
            <li class="{{ $c['e'] }}">
                <span class="mo-dot" aria-hidden="true">{{ ['ok' => '✓', 'info' => 'i', 'warn' => '!', 'bad' => '✕'][$c['e']] }}</span>
                <div><strong>{{ $c['t'] }}</strong><small>{{ $c['d'] }}</small>@if($c['fix'])<small class="fix">Cómo: {{ $c['fix'] }}</small>@endif</div>
            </li>
        @endforeach
    </ul>
</article>
@endforeach
@endsection
