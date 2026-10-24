@extends('layouts.admin')
@section('title', 'Libro de reclamaciones')
@section('heading', 'Libro de reclamaciones')
@section('subheading', 'Reclamos y quejas enviados desde el sitio')

@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-tour.css') }}">@endpush

@section('content')
<article class="panel">
    <header><h2>Registros</h2><span class="tag {{ $pendientes ? 'warn' : 'ok' }}">{{ $pendientes }} pendientes</span></header>
    <div class="list-tools">
        <nav class="range" aria-label="Estado">
            <a href="{{ route('admin.reclamos') }}" @class(['on' => ! $estado])>Todos <small>{{ $total }}</small></a>
            <a href="{{ route('admin.reclamos', ['estado' => 'pendiente']) }}" @class(['on' => $estado === 'pendiente'])>Pendientes</a>
            <a href="{{ route('admin.reclamos', ['estado' => 'atendido']) }}" @class(['on' => $estado === 'atendido'])>Atendidos</a>
        </nav>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Código</th><th>Cliente</th><th>Tipo</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @forelse($reclamos as $r)
                <tr>
                    <td><strong>{{ $r->codigo }}</strong></td>
                    <td>{{ $r->nombre }}<br><small>{{ $r->email }}</small></td>
                    <td>{{ ucfirst($r->tipo) }}</td>
                    <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
                    <td><span class="tag {{ $r->estado === 'atendido' ? 'ok' : 'warn' }}">{{ ucfirst($r->estado) }}</span></td>
                    <td class="actions"><a href="{{ route('admin.reclamos.show', $r->id) }}">Abrir</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Aún no hay reclamaciones.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $reclamos->links('partials.pagination', ['lang' => 'es']) }}
</article>
@endsection
