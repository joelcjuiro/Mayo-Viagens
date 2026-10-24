@extends('layouts.app')
@section('title', 'Libro de Reclamaciones | '.config('legal.name'))

@section('content')
@php
    $codigo = session('codigo');
    $fecha = now()->locale('es')->translatedFormat('j \d\e F \d\e Y');
@endphp

<section class="page-head legal-head">
    <div class="container">
        <p class="eyebrow">Atención al cliente</p>
        <h1>Libro de Reclamaciones</h1>
        <p class="lead-dark">Tu opinión nos ayuda a mejorar. Registra aquí un reclamo o una queja y te responderemos por el correo que indiques.</p>
    </div>
</section>

<section class="form-section">
    <div class="container">
        @if($codigo)
            <div class="reclamo-ok" role="status">
                <span class="reclamo-ok-ico">✓</span>
                <div>
                    <h2>Recibimos tu hoja de reclamación</h2>
                    <p>Tu código de registro es <strong class="reclamo-code">{{ $codigo }}</strong>. Guárdalo para cualquier consulta. Te responderemos al correo que indicaste dentro del plazo que establece la ley.</p>
                    <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">⎙ Imprimir constancia</button>
                </div>
            </div>
        @endif

        <div class="contact-grid">
            <form method="POST" action="{{ route('reclamaciones.store') }}" class="card-form" novalidate>
                @csrf
                <input type="text" name="web" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">

                <h2>Hoja de reclamación</h2>
                <p class="lead-dark" style="margin:0">Fecha: {{ $fecha }}</p>

                @if($errors->any())
                    <div class="alert alert-error" role="alert">Revisa los campos marcados: {{ $errors->first() }}</div>
                @endif

                <h3 class="rec-h">1. Identificación del consumidor</h3>
                <div class="row">
                    <div class="field"><label for="nombre">Nombre completo *</label>
                        <input id="nombre" name="nombre" value="{{ old('nombre') }}" required autocomplete="name">
                        @error('nombre')<small class="error">{{ $message }}</small>@enderror</div>
                    <div class="field"><label for="documento_tipo">Documento *</label>
                        <select id="documento_tipo" name="documento_tipo">
                            @foreach(['DNI','CE','Pasaporte','Otro'] as $d)<option @selected(old('documento_tipo') === $d)>{{ $d }}</option>@endforeach
                        </select></div>
                </div>
                <div class="row">
                    <div class="field"><label for="documento_numero">N.° de documento *</label>
                        <input id="documento_numero" name="documento_numero" value="{{ old('documento_numero') }}" required>
                        @error('documento_numero')<small class="error">{{ $message }}</small>@enderror</div>
                    <div class="field"><label for="telefono">Teléfono</label>
                        <input id="telefono" name="telefono" value="{{ old('telefono') }}" autocomplete="tel"></div>
                </div>
                <div class="row">
                    <div class="field"><label for="email">Correo electrónico *</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                        @error('email')<small class="error">{{ $message }}</small>@enderror</div>
                    <div class="field"><label for="domicilio">Domicilio *</label>
                        <input id="domicilio" name="domicilio" value="{{ old('domicilio') }}" required autocomplete="street-address">
                        @error('domicilio')<small class="error">{{ $message }}</small>@enderror</div>
                </div>
                <div class="field field-check">
                    <label class="check-line"><input type="checkbox" name="menor_edad" value="1" id="menor" @checked(old('menor_edad'))><span>Soy menor de edad (debe completarse con el nombre de tu padre, madre o tutor)</span></label>
                </div>
                <div class="field" id="apoderado-box" @if(!old('menor_edad')) hidden @endif><label for="apoderado">Nombre del padre, madre o tutor</label>
                    <input id="apoderado" name="apoderado" value="{{ old('apoderado') }}">
                    @error('apoderado')<small class="error">{{ $message }}</small>@enderror</div>

                <h3 class="rec-h">2. Identificación del bien contratado</h3>
                <div class="row">
                    <div class="field"><label for="bien">Tipo *</label>
                        <select id="bien" name="bien"><option value="servicio" @selected(old('bien') !== 'producto')>Servicio (tour, traslado…)</option><option value="producto" @selected(old('bien') === 'producto')>Producto</option></select></div>
                    <div class="field"><label for="monto">Monto reclamado (US$)</label>
                        <input id="monto" type="number" step="0.01" min="0" name="monto" value="{{ old('monto') }}">
                        @error('monto')<small class="error">{{ $message }}</small>@enderror</div>
                </div>
                <div class="field"><label for="descripcion_bien">Descripción (tour o servicio y fecha) *</label>
                    <textarea id="descripcion_bien" name="descripcion_bien" rows="2" required>{{ old('descripcion_bien') }}</textarea>
                    @error('descripcion_bien')<small class="error">{{ $message }}</small>@enderror</div>

                <h3 class="rec-h">3. Detalle de la reclamación</h3>
                <div class="field"><label for="tipo">Tipo de reclamación *</label>
                    <select id="tipo" name="tipo">
                        <option value="reclamo" @selected(old('tipo') !== 'queja')>Reclamo — disconformidad con el servicio contratado</option>
                        <option value="queja" @selected(old('tipo') === 'queja')>Queja — malestar con la atención, sin relación con el servicio</option>
                    </select></div>
                <div class="field"><label for="detalle">Detalle *</label>
                    <textarea id="detalle" name="detalle" rows="4" required>{{ old('detalle') }}</textarea>
                    @error('detalle')<small class="error">{{ $message }}</small>@enderror</div>
                <div class="field"><label for="pedido">Pedido del consumidor (qué solicitas) *</label>
                    <textarea id="pedido" name="pedido" rows="3" required>{{ old('pedido') }}</textarea>
                    @error('pedido')<small class="error">{{ $message }}</small>@enderror</div>

                <div class="field field-check">
                    <label class="check-line"><input type="checkbox" name="acepta" value="1" @checked(old('acepta')) required><span>Declaro que la información brindada es verdadera. Acepto el tratamiento de mis datos según la <a href="{{ route('privacidad') }}" target="_blank" rel="noopener">Política de Privacidad</a>.</span></label>
                    @error('acepta')<small class="error">{{ $message }}</small>@enderror
                </div>

                <p class="rec-legal">La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI. El proveedor debe dar respuesta al reclamo en el plazo establecido por la ley.</p>

                <button class="btn btn-gold btn-lg" type="submit">Enviar reclamación</button>
            </form>

            <aside class="contact-info">
                <h2>Datos del proveedor</h2>
                <p><strong>{{ config('legal.name') }}</strong></p>
                @if(config('legal.ruc'))<p>RUC: {{ config('legal.ruc') }}</p>@endif
                @if(config('legal.address'))<p>{{ config('legal.address') }}</p>@endif
                @if(config('legal.email'))<p><a href="mailto:{{ config('legal.email') }}">{{ config('legal.email') }}</a></p>@endif
                <p>WhatsApp: <a href="https://wa.me/{{ config('mayo.whatsapp') }}" target="_blank" rel="noopener">+{{ config('mayo.whatsapp') }}</a></p>
                <hr style="border:0;border-top:1px solid rgba(255,255,255,.15);margin:1.2rem 0">
                <p><strong>Reclamo:</strong> disconformidad relacionada con los productos o servicios.</p>
                <p><strong>Queja:</strong> malestar o descontento respecto a la atención al público.</p>
            </aside>
        </div>
    </div>
</section>

<script>
(function () {
    var chk = document.getElementById('menor'), box = document.getElementById('apoderado-box');
    if (chk && box) { chk.addEventListener('change', function () { box.hidden = !chk.checked; }); }
})();
</script>
@endsection
