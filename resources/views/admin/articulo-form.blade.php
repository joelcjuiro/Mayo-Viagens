@extends('layouts.admin')
@php $editando = $articulo->exists; @endphp
@section('title', $editando ? 'Editar artículo' : 'Nuevo artículo')
@section('heading', $editando ? 'Editar artículo' : 'Nuevo artículo')
@section('subheading', 'Escribe en tu idioma y traduce a los demás con un clic')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-blog.css') }}">@endpush

@section('content')
@php
    $v = fn ($k, $d = null) => old($k, $d);
    $tr = fn ($lang, $campo) => old("tr.$lang.$campo", $articulo->traducciones[$lang][$campo] ?? '');
    $base = in_array(old('idioma_base', $articulo->idioma_base), ['pt', 'es', 'en'], true) ? old('idioma_base', $articulo->idioma_base) : 'es';
    $nombresIdioma = ['es' => 'Español', 'pt' => 'Português', 'en' => 'English'];
    $fechaVal = old('publicado_at', $articulo->publicado_at?->format('Y-m-d\TH:i'));
@endphp
<form method="POST" enctype="multipart/form-data" id="artForm" class="ab-form"
      action="{{ $editando ? route('admin.blog.update', $articulo->id) : route('admin.blog.store') }}"
      data-traducir="{{ route('admin.tours.traducir') }}" data-traductor="{{ $traductor ? '1' : '0' }}">
    @csrf
    @if($editando) @method('PUT') @endif

    @if($errors->any())
        <div class="err-box" role="alert"><strong>Revisa lo siguiente:</strong>
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="ab-cols">
        <div class="ab-main">
            <section class="card-f">
                <h3>¿En qué idioma escribes este artículo?</h3>
                <div class="lang-pills">
                    @foreach($nombresIdioma as $cod => $nom)
                        <label class="lang-pill"><input type="radio" name="idioma_base" value="{{ $cod }}" @checked($base === $cod)><span><b>{{ strtoupper($cod) }}</b> {{ $nom }}</span></label>
                    @endforeach
                </div>
            </section>

            <section class="card-f">
                <h3>Contenido</h3>
                <label class="f">Título *
                    <input type="text" name="titulo" id="aTitulo" maxlength="160" required value="{{ $v('titulo', $articulo->titulo) }}" placeholder="Ej: Novas regras de ingresso a Machu Picchu">
                </label>
                <label class="f">Resumen <small>(1–2 frases; aparece en las tarjetas y en Google)</small>
                    <textarea name="resumen" id="aResumen" rows="2" maxlength="320">{{ $v('resumen', $articulo->resumen) }}</textarea>
                    <small class="cnt"><span data-count-for="aResumen">0</span>/320</small>
                </label>
                <div class="f">Texto del artículo *
                    <div class="md-bar" role="toolbar" aria-label="Formato">
                        <button type="button" data-md="b" title="Negrita"><b>B</b></button>
                        <button type="button" data-md="i" title="Cursiva"><i>I</i></button>
                        <button type="button" data-md="h2" title="Título de sección">H2</button>
                        <button type="button" data-md="h3" title="Subtítulo">H3</button>
                        <button type="button" data-md="ul" title="Lista">• Lista</button>
                        <button type="button" data-md="ol" title="Lista numerada">1. Lista</button>
                        <button type="button" data-md="q" title="Cita o aviso destacado">❝</button>
                        <button type="button" data-md="a" title="Enlace">🔗 Enlace</button>
                    </div>
                    <textarea name="contenido" id="aContenido" rows="16" required>{{ $v('contenido', $articulo->contenido) }}</textarea>
                    <small>Formato simple: <code>## Título</code>, <code>**negrita**</code>, <code>- lista</code>, <code>[texto](https://enlace)</code>. Deja una línea en blanco entre párrafos.</small>
                </div>
            </section>

            <section class="card-f">
                <h3>Traducciones</h3>
                <p class="muted-p">Solo se piden los idiomas que faltan. Si dejas un campo vacío, el sitio muestra el texto original.</p>
                @if($traductor)<button type="button" class="btn-ghost" id="btnTraducir">✨ Traducir automáticamente a los idiomas que faltan</button> <small id="trMsg" role="status"></small>
                @else<small>La traducción automática no está configurada (clave DeepL). Puedes escribir las traducciones a mano.</small>@endif
                @foreach($nombresIdioma as $l => $nombre)
                    <details @class(['tr-box', 'lang-off' => $l === $base]) data-lang-card="{{ $l }}" {{ $tr($l, 'titulo') !== '' ? 'open' : '' }}>
                        <summary>{{ $nombre }}</summary>
                        <label class="f">Título<input type="text" name="tr[{{ $l }}][titulo]" data-tr="{{ $l }}:titulo" maxlength="160" value="{{ $tr($l, 'titulo') }}"></label>
                        <label class="f">Resumen<textarea name="tr[{{ $l }}][resumen]" data-tr="{{ $l }}:resumen" rows="2" maxlength="320">{{ $tr($l, 'resumen') }}</textarea></label>
                        <label class="f">Texto<textarea name="tr[{{ $l }}][contenido]" data-tr="{{ $l }}:contenido" rows="10">{{ $tr($l, 'contenido') }}</textarea></label>
                        <label class="f">Descripción para Google<textarea name="tr[{{ $l }}][meta_descripcion]" data-tr="{{ $l }}:meta_descripcion" rows="2" maxlength="200">{{ $tr($l, 'meta_descripcion') }}</textarea></label>
                        <label class="f">Texto alternativo de la portada<input type="text" name="tr[{{ $l }}][imagen_alt]" data-tr="{{ $l }}:imagen_alt" maxlength="160" value="{{ $tr($l, 'imagen_alt') }}"></label>
                    </details>
                @endforeach
            </section>
        </div>

        <aside class="ab-side">
            <section class="card-f">
                <h3>Publicación</h3>
                <label class="f">Categoría
                    <select name="categoria">@foreach(\App\Models\Articulo::CATEGORIAS as $c)<option value="{{ $c }}" @selected($v('categoria', $articulo->categoria) === $c)>{{ __('blog.cats.'.$c) }}</option>@endforeach</select>
                </label>
                <label class="f">Fecha y hora de publicación <small>(vacío = ahora; futura = se programa)</small>
                    <input type="datetime-local" name="publicado_at" value="{{ $fechaVal }}">
                </label>
                <label class="chk"><input type="checkbox" name="destacado" value="1" @checked($v('destacado', $articulo->destacado))> Destacar al inicio del blog</label>
                <div class="ab-actions">
                    <button type="submit" name="accion" value="publicar" class="btn-admin">{{ $articulo->situacion === 'publicado' ? 'Guardar cambios' : 'Publicar' }}</button>
                    <button type="submit" name="accion" value="borrador" class="btn-ghost">Guardar borrador</button>
                </div>
                @if($editando)<p><a href="{{ route('admin.blog.preview', $articulo->id) }}" target="_blank" rel="noopener">Ver vista previa ↗</a></p>@endif
            </section>

            <section class="card-f">
                <h3>Portada</h3>
                @if($articulo->imagen_url)
                    <img src="{{ $articulo->miniatura_url }}" alt="" class="cover-prev" width="320" height="200" loading="lazy">
                    <label class="chk"><input type="checkbox" name="quitar_portada" value="1"> Quitar portada actual</label>
                @endif
                <label class="f">{{ $articulo->imagen ? 'Cambiar portada' : 'Subir portada' }} <small>(cualquier peso: se reduce sola)</small>
                    <input type="file" name="portada" id="aPortada" accept="image/jpeg,image/png,image/webp">
                </label>
                <small id="aPortadaMsg" role="status"></small>
                <label class="f">Texto alternativo <small>(describe la foto; ayuda a Google y accesibilidad)</small>
                    <input type="text" name="imagen_alt" maxlength="160" value="{{ $v('imagen_alt', $articulo->imagen_alt) }}">
                </label>
            </section>

            <section class="card-f">
                <h3>Google</h3>
                <label class="f">URL del artículo <small>(se genera sola del título)</small>
                    <div class="url-box"><span>/blog/</span><input type="text" name="slug" id="aSlug" maxlength="180" value="{{ $v('slug', $articulo->slug) }}" placeholder="se-genera-sola" autocomplete="off"></div>
                </label>
                <label class="f">Descripción para Google <small>(máx. 200)</small>
                    <textarea name="meta_descripcion" rows="3" maxlength="200">{{ $v('meta_descripcion', $articulo->meta_descripcion) }}</textarea>
                </label>
            </section>
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script src="{{ \App\Support\Media::v('js/img-compress.js') }}"></script>
<script src="{{ asset('js/admin-blog.js') }}"></script>
@endpush
