@extends('layouts.admin')
@section('title', 'Mensajes')
@section('heading', 'Mensajes')
@section('subheading', 'Consultas enviadas desde el formulario de contacto')

@section('content')
<article class="panel">
    @forelse($contactos as $c)
        <div @class(['msg-card', 'unread' => ! $c->leido])>
            <div class="msg-head">
                <h3>{{ $c->asunto ?? 'Sin asunto' }}</h3>
                <form method="POST" action="{{ route('admin.contactos.update', $c->id) }}">
                    @csrf @method('PATCH')
                    <button class="link-btn" type="submit">{{ $c->leido ? 'Marcar como no leído' : 'Marcar como leído' }}</button>
                </form>
            </div>
            <p class="meta">
                {{ $c->nombre }} · <a href="mailto:{{ $c->email }}">{{ $c->email }}</a>
                @if($c->telefono) · {{ $c->telefono }}@endif
                · {{ $c->created_at->format('d/m/Y H:i') }}
            </p>
            <p>{!! nl2br(e($c->mensaje)) !!}</p>
        </div>
    @empty
        <p class="empty">Todavía no hay mensajes.</p>
    @endforelse

    {{ $contactos->links('partials.pagination', ['lang' => 'es']) }}
</article>
@endsection
