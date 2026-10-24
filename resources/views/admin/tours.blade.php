@extends('layouts.admin')
@section('title', 'Tours')
@section('heading', 'Tours')
@section('subheading', 'Crea, edita y decide dónde se muestra cada tour')

@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-tour.css') }}">@endpush

@section('content')
@php
    $tabs = ['' => ['Todos', $conteo['todos']], 'publicado' => ['Publicados', $conteo['publicado']], 'borrador' => ['Borradores', $conteo['borrador']], 'oculto' => ['Ocultos', $conteo['oculto']]];
    $tagEstado = ['publicado' => ['ok', 'Publicado'], 'borrador' => ['warn', 'Borrador'], 'oculto' => ['muted', 'Oculto']];
@endphp
<article class="panel">
    <header>
        <h2>Todos los tours</h2>
        <a class="btn-admin" href="{{ route('admin.tours.create') }}">+ Nuevo tour</a>
    </header>

    <div class="list-tools">
        <nav class="range" aria-label="Estado">
            @foreach($tabs as $key => [$label, $n])
                <a href="{{ route('admin.tours', array_filter(['estado' => $key, 'q' => $q, 'seccion' => $seccionId])) }}" @class(['on' => ($estado ?? '') === $key])>{{ $label }} <small>{{ $n }}</small></a>
            @endforeach
        </nav>
        <form method="GET" class="filters-row">
            @if($estado)<input type="hidden" name="estado" value="{{ $estado }}">@endif
            <input type="search" name="q" value="{{ $q }}" placeholder="Buscar por nombre o URL…" aria-label="Buscar">
            <select name="seccion" onchange="this.form.submit()" aria-label="Sección del inicio">
                <option value="">Todas las secciones</option>
                @foreach($secciones as $s)<option value="{{ $s->id }}" @selected($seccionId == $s->id)>{{ $s->nombre }}</option>@endforeach
            </select>
            <button class="btn-ghost" type="submit">Buscar</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Tour</th><th>Secciones del inicio</th><th>Precio desde</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
            <tbody>
            @forelse($tours as $t)
                @php [$cls, $txt] = $tagEstado[$t->estado]; @endphp
                <tr>
                    <td class="with-img">
                        @if($t->imagen_url)<img src="{{ $t->imagen_url }}" alt="" width="56" height="40" loading="lazy" onerror="this.style.visibility='hidden'">@endif
                        <span><strong>{{ $t->nombre }}</strong><br><small>/tour/{{ $t->slug }}</small></span>
                    </td>
                    <td>
                        @forelse($t->secciones as $s)<span class="chip-sec">{{ $s->nombre }}</span>@empty<small class="muted-s">Sin sección</small>@endforelse
                    </td>
                    <td>@if($t->precio > 0)US$ {{ number_format($t->precio, 0) }}@if($t->precios->count() > 1) <small>({{ $t->precios->count() }} precios)</small>@endif @else — @endif</td>
                    <td><span class="tag {{ $cls }}">{{ $txt }}</span></td>
                    <td class="actions">
                        <a href="{{ route('admin.tours.edit', $t->id) }}">Editar</a>
                        <a href="{{ route('admin.tours.preview', $t->id) }}" target="_blank" rel="noopener">Ver</a>
                        <form method="POST" action="{{ route('admin.tours.estado', $t->id) }}">
                            @csrf @method('PATCH')
                            @if($t->estado === 'publicado')
                                <input type="hidden" name="accion" value="ocultar"><button class="link-btn" type="submit">Ocultar</button>
                            @else
                                <input type="hidden" name="accion" value="mostrar"><button class="link-btn" type="submit">Publicar</button>
                            @endif
                        </form>
                        <form method="POST" action="{{ route('admin.tours.duplicar', $t->id) }}">@csrf<button class="link-btn" type="submit">Duplicar</button></form>
                        @if(auth()->user()->esAdmin())<form method="POST" action="{{ route('admin.tours.delete', $t->id) }}" onsubmit="return confirm('¿Eliminar el tour «{{ addslashes($t->nombre) }}»? Las reservas asociadas se conservan sin tour.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-danger">Eliminar</button>
                        </form>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No hay tours con ese filtro. <a href="{{ route('admin.tours.create') }}">Crea uno nuevo</a>.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $tours->links('partials.pagination', ['lang' => 'es']) }}
</article>
@endsection
