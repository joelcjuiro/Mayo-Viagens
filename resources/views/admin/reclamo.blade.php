@extends('layouts.admin')
@section('title', $r->codigo)
@section('heading', 'Reclamación '.$r->codigo)
@section('subheading', 'Recibida el '.$r->created_at->format('d/m/Y H:i'))

@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-tour.css') }}">@endpush

@section('content')
@if($errors->any())<div class="err-box" role="alert">{{ $errors->first() }}</div>@endif
<div class="editor">
    <div class="card-f">
        <h3>Consumidor</h3>
        <div class="fgrid">
            <div class="f">Nombre<span style="font-weight:400">{{ $r->nombre }}</span></div>
            <div class="f">Documento<span style="font-weight:400">{{ $r->documento_tipo }} {{ $r->documento_numero }}</span></div>
            <div class="f">Correo<span style="font-weight:400"><a href="mailto:{{ $r->email }}">{{ $r->email }}</a></span></div>
            <div class="f">Teléfono<span style="font-weight:400">{{ $r->telefono ?: '—' }}</span></div>
            <div class="f">Domicilio<span style="font-weight:400">{{ $r->domicilio }}</span></div>
            @if($r->menor_edad)<div class="f">Padre/madre/tutor<span style="font-weight:400">{{ $r->apoderado }}</span></div>@endif
        </div>
    </div>
    <div class="card-f">
        <h3>{{ ucfirst($r->tipo) }} · {{ ucfirst($r->bien) }}@if($r->monto) · US$ {{ number_format($r->monto, 2) }}@endif</h3>
        <div class="f">Descripción del bien<span style="font-weight:400">{{ $r->descripcion_bien }}</span></div>
        <div class="f">Detalle<span style="font-weight:400">{!! nl2br(e($r->detalle)) !!}</span></div>
        <div class="f">Pedido del consumidor<span style="font-weight:400">{!! nl2br(e($r->pedido)) !!}</span></div>
    </div>
    <form method="POST" action="{{ route('admin.reclamos.update', $r->id) }}" class="card-f">
        @csrf @method('PATCH')
        <h3>Respuesta del proveedor</h3>
        <label class="f">Estado
            <select name="estado"><option value="pendiente" @selected($r->estado === 'pendiente')>Pendiente</option><option value="atendido" @selected($r->estado === 'atendido')>Atendido</option></select>
        </label>
        <label class="f">Respuesta dada al cliente
            <textarea name="respuesta" rows="5">{{ old('respuesta', $r->respuesta) }}</textarea>
        </label>
        @if($r->atendido_at)<small class="hint-s">Atendido el {{ $r->atendido_at->format('d/m/Y H:i') }}</small>@endif
        <div><button class="btn-admin" type="submit">Guardar</button> <a class="btn-ghost" href="{{ route('admin.reclamos') }}">Volver</a></div>
    </form>
</div>
@endsection
