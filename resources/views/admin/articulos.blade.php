@extends('layouts.admin')
@section('title', 'Blog')
@section('heading', 'Blog y novedades')
@section('subheading', 'Publica actualizaciones, avisos y guías para tus viajeros')

@section('content')
@php
    $tabs = ['' => ['Todos', $conteo['todos']], 'publicado' => ['Publicados', $conteo['publicado']], 'programado' => ['Programados', $conteo['programado']], 'borrador' => ['Borradores', $conteo['borrador']]];
    $tag = ['publicado' => ['ok', 'Publicado'], 'programado' => ['warn', 'Programado'], 'borrador' => ['muted', 'Borrador']];
@endphp
<article class="panel">
    <header>
        <h2>Artículos <small style="color:var(--muted);font-weight:600">· {{ number_format($vistasTotales) }} lecturas en total</small></h2>
        <a class="btn-admin" href="{{ route('admin.blog.create') }}">+ Nuevo artículo</a>
    </header>

    <div class="list-tools">
        <nav class="range" aria-label="Estado">
            @foreach($tabs as $k => [$label, $n])<a href="{{ route('admin.blog', array_filter(['estado' => $k, 'q' => $q])) }}" @class(['on' => ($estado ?? '') === $k])>{{ $label }} <small>{{ $n }}</small></a>@endforeach
        </nav>
        <form method="GET" class="filters-row">
            @if($estado)<input type="hidden" name="estado" value="{{ $estado }}">@endif
            <input type="search" name="q" value="{{ $q }}" placeholder="Buscar por título…" aria-label="Buscar">
            <button class="btn-ghost" type="submit">Buscar</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Artículo</th><th>Categoría</th><th>Estado</th><th>Lecturas</th><th></th></tr></thead>
            <tbody>
            @forelse($articulos as $a)
                <tr>
                    <td>
                        <div class="with-img">
                            @if($a->miniatura_url)<img src="{{ $a->miniatura_url }}" alt="" width="64" height="44" loading="lazy" style="object-fit:cover;border-radius:8px">@endif
                            <div><strong>{{ $a->titulo }}</strong>@if($a->destacado) <span class="tag ok">★ Destacado</span>@endif<br>
                                <small style="color:var(--muted)">/blog/{{ $a->slug }} · {{ $a->updated_at->diffForHumans() }}</small></div>
                        </div>
                    </td>
                    <td>{{ __('blog.cats.'.$a->categoria) }}</td>
                    <td><span class="tag {{ $tag[$a->situacion][0] }}">{{ $tag[$a->situacion][1] }}</span>
                        @if($a->situacion === 'programado')<br><small>{{ $a->publicado_at->format('d/m/Y H:i') }}</small>@endif</td>
                    <td>{{ number_format($a->vistas) }}</td>
                    <td class="actions">
                        <a href="{{ route('admin.blog.edit', $a->id) }}">Editar</a>
                        <a href="{{ route('admin.blog.preview', $a->id) }}" target="_blank" rel="noopener">Vista previa</a>
                        <form method="POST" action="{{ route('admin.blog.estado', $a->id) }}" style="display:inline">
                            @csrf @method('PATCH')
                            <input type="hidden" name="accion" value="{{ $a->situacion === 'borrador' ? 'publicar' : 'ocultar' }}">
                            <button type="submit" class="link-btn">{{ $a->situacion === 'borrador' ? 'Publicar' : 'Pasar a borrador' }}</button>
                        </form>
                        @if(auth()->user()->esAdmin())
                        <form method="POST" action="{{ route('admin.blog.delete', $a->id) }}" style="display:inline" onsubmit="return confirm('¿Eliminar este artículo? No se puede deshacer.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-btn link-danger">Eliminar</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--muted)">Aún no hay artículos. Escribe el primero: una novedad, un aviso o una guía para tus viajeros.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $articulos->links() }}
</article>
@endsection
