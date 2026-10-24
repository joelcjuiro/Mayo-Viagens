@extends('layouts.admin')
@section('title', 'Usuarios')
@section('heading', 'Usuarios del panel')
@section('subheading', 'Quién puede entrar y qué puede hacer')

@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-tour.css') }}">@endpush

@section('content')
@if($errors->any())<div class="err-box" role="alert">{{ $errors->first() }}</div>@endif

<article class="panel">
    <header><h2>Permisos por rol</h2></header>
    <div class="fgrid">
        <div class="f"><strong>Administrador</strong><span style="font-weight:400">Todo: tours, secciones, opiniones, reservas, mensajes, reclamos, eliminar contenido y administrar usuarios.</span></div>
        <div class="f"><strong>Editor</strong><span style="font-weight:400">Crea y edita tours y secciones, modera opiniones, atiende reservas, mensajes y reclamos. <b>No puede eliminar</b> ni administrar usuarios.</span></div>
    </div>
</article>

<article class="panel">
    <header><h2>Usuarios</h2><span class="tag muted">{{ $usuarios->count() }}</span></header>
    <div class="op-admin">
        @foreach($usuarios as $u)
            <form method="POST" action="{{ route('admin.usuarios.update', $u->id) }}" class="op-admin-card">
                @csrf @method('PUT')
                <div class="op-admin-head">
                    <strong>{{ $u->name }}</strong>
                    <span class="tag {{ $u->esAdmin() ? 'ok' : 'muted' }}">{{ $u->esAdmin() ? 'Administrador' : 'Editor' }}</span>
                    @unless($u->activo)<span class="tag warn">Desactivado</span>@endunless
                    @if($u->id === auth()->id())<small>(tú)</small>@endif
                </div>
                <div class="fgrid">
                    <label class="f">Nombre<input type="text" name="name" value="{{ $u->name }}" required maxlength="100"></label>
                    <label class="f">Correo<input type="text" name="email" value="{{ $u->email }}" required maxlength="150"></label>
                    <label class="f">Rol
                        <select name="role"><option value="editor" @selected(! $u->esAdmin())>Editor</option><option value="admin" @selected($u->esAdmin())>Administrador</option></select></label>
                    <label class="f">Nueva contraseña <small>(déjala vacía para no cambiarla)</small>
                        <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="••••••••"></label>
                </div>
                <div class="op-admin-actions">
                    <label class="check-line"><input type="checkbox" name="activo" value="1" @checked($u->activo)> <span>Puede entrar</span></label>
                    <button class="btn-admin" type="submit">Guardar</button>
                    @if($u->id !== auth()->id())
                        <button class="link-danger" type="submit" form="del-u-{{ $u->id }}" onclick="return confirm('¿Eliminar a {{ addslashes($u->name) }}?')">Eliminar</button>
                    @endif
                </div>
            </form>
            @if($u->id !== auth()->id())<form id="del-u-{{ $u->id }}" method="POST" action="{{ route('admin.usuarios.delete', $u->id) }}" hidden>@csrf @method('DELETE')</form>@endif
        @endforeach
    </div>
</article>

<article class="panel">
    <header><h2>Nuevo usuario</h2></header>
    <form method="POST" action="{{ route('admin.usuarios.store') }}" class="editor">
        @csrf
        <div class="fgrid">
            <label class="f">Nombre<input type="text" name="name" value="{{ old('name') }}" required maxlength="100"></label>
            <label class="f">Correo (para iniciar sesión)<input type="email" name="email" value="{{ old('email') }}" required maxlength="150"></label>
            <label class="f">Contraseña (mín. 8 caracteres)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
            <label class="f">Rol
                <select name="role"><option value="editor">Editor</option><option value="admin">Administrador</option></select></label>
        </div>
        <div><button class="btn-admin" type="submit">Crear usuario</button></div>
    </form>
</article>
@endsection
