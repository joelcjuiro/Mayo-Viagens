@php
    $editable = $editable ?? false;
    $tags = ['pendiente' => 'warn', 'confirmada' => 'ok', 'cancelada' => 'bad'];
@endphp
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Cliente</th><th>Tour</th><th>Fecha</th><th>Personas</th><th>Estado</th>
                @if($editable)<th>Contacto</th>@endif
            </tr>
        </thead>
        <tbody>
        @forelse($reservas as $r)
            <tr>
                <td>
                    <strong>{{ $r->nombre }}</strong><br>
                    <small>{{ $r->email }} · {{ $r->telefono }}</small>
                    @if($editable && $r->mensaje)<br><small title="{{ $r->mensaje }}">“{{ \Illuminate\Support\Str::limit($r->mensaje, 70) }}”</small>@endif
                </td>
                <td>{{ $r->tour->nombre ?? 'Sin definir' }}</td>
                <td>{{ $r->fecha->format('d/m/Y') }}</td>
                <td>{{ $r->personas }}</td>
                <td>
                    @if($editable)
                        <form method="POST" action="{{ route('admin.reservas.update', $r->id) }}">
                            @csrf @method('PATCH')
                            <select name="estado" class="state-select" onchange="this.form.submit()" aria-label="Estado de la reserva de {{ $r->nombre }}">
                                @foreach(['pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'cancelada' => 'Cancelada'] as $valor => $texto)
                                    <option value="{{ $valor }}" @selected($r->estado === $valor)>{{ $texto }}</option>
                                @endforeach
                            </select>
                        </form>
                    @else
                        <span class="tag {{ $tags[$r->estado] ?? 'muted' }}">{{ ucfirst($r->estado) }}</span>
                    @endif
                </td>
                @if($editable)
                    <td class="actions">
                        <a href="https://wa.me/{{ preg_replace('/\D+/', '', $r->telefono) }}" target="_blank" rel="noopener">WhatsApp</a>
                        <a href="mailto:{{ $r->email }}">Correo</a>
                    </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="{{ $editable ? 6 : 5 }}" class="empty">Todavía no hay reservas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
