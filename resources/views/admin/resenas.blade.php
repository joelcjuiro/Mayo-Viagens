@extends('layouts.admin')
@section('title', 'Opiniones')
@section('heading', 'Opiniones de viajeros')
@section('subheading', 'Aprueba lo que se publica en el sitio')

@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-tour.css') }}">@endpush

@section('content')
@php $tabs = ['' => ['Todas', $conteo['todas']], 'pendiente' => ['Pendientes', $conteo['pendiente']], 'aprobada' => ['Aprobadas', $conteo['aprobada']], 'rechazada' => ['Rechazadas', $conteo['rechazada']]]; @endphp
<article class="panel">
    <header><h2>Opiniones</h2><span class="tag {{ $conteo['pendiente'] ? 'warn' : 'ok' }}">{{ $conteo['pendiente'] }} por revisar</span></header>
    <div class="list-tools">
        <nav class="range" aria-label="Estado">
            @foreach($tabs as $k => [$label, $n])<a href="{{ route('admin.resenas', array_filter(['estado' => $k])) }}" @class(['on' => ($estado ?? '') === $k])>{{ $label }} <small>{{ $n }}</small></a>@endforeach
        </nav>
    </div>

    <div class="op-admin">
        @forelse($resenas as $r)
            <form method="POST" action="{{ route('admin.resenas.update', $r->id) }}" class="op-admin-card">
                @csrf @method('PATCH')
                <div class="op-admin-head">
                    <strong>{{ $r->nombre }}</strong> <small>{{ $r->pais }} · {{ $r->email }}</small>
                    <span class="stars">{{ str_repeat('★', $r->calificacion) }}</span>
                    <span class="tag {{ ['pendiente' => 'warn', 'aprobada' => 'ok', 'rechazada' => 'muted'][$r->estado] }}">{{ ucfirst($r->estado) }}</span>
                </div>
                @if($r->tour)<small>Tour: {{ $r->tour->nombre }}</small>@endif
                <p>{{ $r->comentario }}</p>
                @if($r->fotos)<div class="op-photos">@foreach($r->fotosUrls() as $u)<a href="{{ $u }}" target="_blank" rel="noopener"><img src="{{ $u }}" alt="" loading="lazy"></a>@endforeach</div>@endif
                <label class="f">Respuesta pública de la agencia (opcional)
                    <textarea name="respuesta" rows="2" maxlength="1000">{{ $r->respuesta }}</textarea></label>
                <div class="op-admin-actions">
                    <button class="btn-admin" name="estado" value="aprobada" type="submit">Aprobar y publicar</button>
                    <button class="btn-ghost" name="estado" value="rechazada" type="submit">Rechazar</button>
                    <button class="btn-ghost" name="estado" value="pendiente" type="submit">Dejar pendiente</button>
                    @if(auth()->user()->esAdmin())<button class="link-danger" type="submit" form="del-op-{{ $r->id }}" onclick="return confirm('¿Eliminar esta opinión?')">Eliminar</button>@endif
                </div>
            </form>
            <form id="del-op-{{ $r->id }}" method="POST" action="{{ route('admin.resenas.delete', $r->id) }}" hidden>@csrf @method('DELETE')</form>
        @empty
            <p class="empty">No hay opiniones con ese filtro.</p>
        @endforelse
    </div>
    {{ $resenas->links('partials.pagination', ['lang' => 'es']) }}
</article>
@endsection
