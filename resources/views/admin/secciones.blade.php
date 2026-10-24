@extends('layouts.admin')
@section('title', 'Secciones del inicio')
@section('heading', 'Secciones del inicio')
@section('subheading', 'Cada sección aparece en la página principal con los tours que le asignes')

@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-tour.css') }}">@endpush

@section('content')
@if($errors->any())
    <div class="errors" role="alert">Revisa el formulario:<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<article class="panel">
    <header><h2>Secciones</h2><span class="tag muted">{{ $secciones->count() }}</span></header>
    <p class="hint">Una sección con 0 tours publicados no se muestra en el inicio. Los números menores salen primero. Para asignar tours, abre el tour y usa el panel «Publicación».</p>
    <div class="sec-list">
        @forelse($secciones as $s)
            <form method="POST" action="{{ route('admin.secciones.update', $s->id) }}" class="sec-row">
                @csrf @method('PUT')
                <input type="number" name="orden" value="{{ $s->orden }}" min="0" max="999" aria-label="Orden" class="w-orden">
                <input type="text" name="nombre" value="{{ $s->nombre }}" required maxlength="100" aria-label="Nombre">
                <input type="text" name="descripcion" value="{{ $s->descripcion }}" maxlength="200" placeholder="Texto corto bajo el título (opcional)" aria-label="Descripción">
                <label class="check"><input type="checkbox" name="activo" value="1" @checked($s->activo)> Visible</label>
                <span class="tag {{ $s->publicos_count ? 'ok' : 'muted' }}" title="Tours publicados / total">{{ $s->publicos_count }}/{{ $s->tours_count }} tours</span>
                <button class="btn-ghost" type="submit">Guardar</button>
                <details class="trad" style="flex-basis:100%">
                    <summary>Traducciones (opcional)</summary>
                    <div class="fgrid">
                    @foreach(['es' => 'Español', 'en' => 'English'] as $l => $ln)
                        <label class="f">Nombre · {{ $ln }}<input type="text" name="tr[{{ $l }}][nombre]" value="{{ $s->traducciones[$l]['nombre'] ?? '' }}" maxlength="200"></label>
                        <label class="f">Texto corto · {{ $ln }}<input type="text" name="tr[{{ $l }}][descripcion]" value="{{ $s->traducciones[$l]['descripcion'] ?? '' }}" maxlength="200"></label>
                    @endforeach
                    </div>
                </details>
                <button class="link-danger" type="submit" form="del-{{ $s->id }}" onclick="return confirm('¿Eliminar la sección «{{ addslashes($s->nombre) }}»? Los tours no se borran.')">Eliminar</button>
            </form>
            <form id="del-{{ $s->id }}" method="POST" action="{{ route('admin.secciones.delete', $s->id) }}" hidden>@csrf @method('DELETE')</form>
        @empty
            <p class="empty">Aún no hay secciones. Crea la primera abajo.</p>
        @endforelse
    </div>
</article>

<article class="panel">
    <header><h2>Nueva sección</h2></header>
    <form method="POST" action="{{ route('admin.secciones.store') }}" class="sec-row">
        @csrf
        <input type="text" name="nombre" required maxlength="100" placeholder="Ej: Tours en Cusco" aria-label="Nombre" value="{{ old('nombre') }}">
        <input type="text" name="descripcion" maxlength="200" placeholder="Texto corto (opcional)" aria-label="Descripción" value="{{ old('descripcion') }}">
        <button class="btn-admin" type="submit">Crear sección</button>
    </form>
</article>
@endsection
