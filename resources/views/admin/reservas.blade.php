@extends('layouts.admin')
@section('title', 'Reservas')
@section('heading', 'Reservas')
@section('subheading', 'Solicitudes recibidas desde el sitio web')

@section('content')
<article class="panel">
    <header>
        <form method="GET" class="filters">
            <select name="estado" onchange="this.form.submit()" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach(['pendiente' => 'Pendientes', 'confirmada' => 'Confirmadas', 'cancelada' => 'Canceladas'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </form>
    </header>
    @include('admin.partials.reservas-table', ['reservas' => $reservas, 'editable' => true])
    {{ $reservas->links('partials.pagination', ['lang' => 'es']) }}
</article>
@endsection
