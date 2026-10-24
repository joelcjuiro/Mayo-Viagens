@extends('layouts.admin')
@php
    $editando = $tour->exists;
    $estadoTxt = ! $editando ? 'Nuevo' : ['publicado' => 'Publicado', 'borrador' => 'Borrador', 'oculto' => 'Oculto'][$tour->estado];
@endphp
@section('title', $editando ? 'Editar tour' : 'Nuevo tour')
@section('heading', $editando ? 'Editar: '.\Illuminate\Support\Str::limit($tour->nombre, 40) : 'Nuevo tour')
@section('subheading', 'Completa los paneles. Puedes guardar como borrador en cualquier momento.')

@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-tour.css') }}">@endpush

@section('content')
@php
    // Valores: lo que se escribió (si hubo errores) o lo guardado
    $v = fn ($k, $d = null) => old($k, $d);

    $galeriaVista = old('imagenes_orden') !== null
        ? collect(old('imagenes_orden'))->filter(fn ($t) => str_starts_with($t, 'old:'))->map(fn ($t) => ['ruta' => substr($t, 4), 'url' => \App\Models\Tour::urlDe(substr($t, 4))])->values()->all()
        : $imagenes;

    $preciosVista = old('precios') !== null ? array_values(old('precios')) : $precios;
    if (! $preciosVista) { $preciosVista = [['etiqueta' => 'Por persona', 'precio' => '', 'descripcion' => '']]; }

    $pasosVista = old('itinerario') !== null
        ? collect(old('itinerario'))->map(fn ($p) => [
            'titulo' => $p['titulo'] ?? '', 'horario' => $p['horario'] ?? '', 'descripcion' => $p['descripcion'] ?? '', 'tr' => $p['tr'] ?? [],
            'imagenes' => collect($p['imagenes_orden'] ?? [])->filter(fn ($t) => str_starts_with($t, 'old:'))->map(fn ($t) => substr($t, 4))->values()->all(),
        ])->values()->all()
        : $pasos;
    if (! $pasosVista) { $pasosVista = [[]]; }

    $incluyeVista = old('incluye_items') !== null ? array_values(array_filter(old('incluye_items'), 'strlen')) : $incluye;
    $noIncluyeVista = old('no_incluye_items') !== null ? array_values(array_filter(old('no_incluye_items'), 'strlen')) : $noIncluye;
    $adicionalesVista = old('adicionales') !== null ? array_values(old('adicionales')) : $adicionales;
    $seccionesSel = old('secciones') !== null ? array_map('intval', old('secciones')) : $idsSecciones;
    $tr = fn ($lang, $campo) => old("traducciones.$lang.$campo", $tour->traducciones[$lang][$campo] ?? '');

    // Qué panel tiene errores (para marcarlo y abrirlo)
    $panelDe = function ($key) {
        return match (true) {
            str_starts_with($key, 'itinerario') => 'itinerario',
            str_starts_with($key, 'precios'), $key === 'descuento' => 'precio',
            str_starts_with($key, 'secciones'), in_array($key, ['meta_descripcion', 'palabras_clave']) => 'publicacion',
            str_starts_with($key, 'traducciones') => 'idiomas',
            default => 'detalle',
        };
    };
    $panelesConError = collect($errors->keys())->map($panelDe)->unique()->values()->all();
    $panelInicial = request('panel') ?: ($panelesConError[0] ?? 'detalle');

    $nombresPanel = ['detalle' => 'Tour', 'itinerario' => 'Itinerario', 'precio' => 'Precio', 'publicacion' => 'Publicación', 'idiomas' => 'Idiomas'];
@endphp

<form method="POST" enctype="multipart/form-data" id="tourForm" class="editor"
      action="{{ $editando ? route('admin.tours.update', $tour->id) : route('admin.tours.store') }}"
      data-max-kb="{{ $maxKb }}" data-initial-panel="{{ $panelInicial }}" data-errors='@json($panelesConError)'
      data-publicado="{{ $editando && $tour->estado === 'publicado' ? '1' : '0' }}" novalidate>
    @csrf
    @if($editando) @method('PUT') @endif
    <input type="hidden" name="panel_actual" id="panelActual" value="{{ $panelInicial }}">

    @if($errors->any())
        <div class="err-box" role="alert">
            <strong>Revisa lo siguiente:</strong>
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            @if(old('_token') && $errors->any())<small>Si habías elegido imágenes nuevas, vuelve a seleccionarlas: el navegador no las conserva tras un error.</small>@endif
        </div>
    @endif

    <nav class="panel-tabs" role="tablist" aria-label="Paneles del tour">
        @foreach($nombresPanel as $k => $label)
            <button type="button" role="tab" data-tab="{{ $k }}" aria-selected="false" aria-controls="pane-{{ $k }}">
                <i class="dot-s" data-dot="{{ $k }}"></i>{{ $label }}
            </button>
        @endforeach
    </nav>

    {{-- ================= PANEL: TOUR ================= --}}
    <section class="tab-pane" id="pane-detalle" data-pane="detalle" role="tabpanel" hidden>
        <div class="card-f">
            <h3>Datos del tour</h3>
            <div class="fgrid">
                <label class="f">Nombre del tour *
                    <input type="text" name="nombre" id="fNombre" value="{{ $v('nombre', $tour->nombre) }}" maxlength="150" required placeholder="Ej: Machu Picchu 2 días">
                </label>
                <div class="f">URL del tour <small>(para que lo encuentren en Google y en el buscador)</small>
                    <div class="url-box"><span>/tour/</span><input type="text" name="slug" id="fSlug" value="{{ $v('slug', $tour->slug) }}" maxlength="150" placeholder="machu-picchu-2-dias" autocomplete="off" data-auto="{{ $editando ? '0' : '1' }}"></div>
                    <small>Enlace final: <span class="url-prev" id="urlPrev">{{ url('/tour') }}/{{ $v('slug', $tour->slug) }}</span></small>
                </div>
            </div>
            <div class="fgrid">
                <label class="f">Duración
                    <input type="text" name="duracion" value="{{ $v('duracion', $tour->duracion) }}" maxlength="100" placeholder="Día completo, 2 días / 1 noche…">
                </label>
                <label class="f">Destino
                    <input type="text" name="destino" list="dl-destinos" value="{{ $v('destino', $tour->destino) }}" maxlength="100" placeholder="Cusco, Lima, Puno, Arequipa…">
                    <datalist id="dl-destinos"><option value="Cusco"><option value="Lima"><option value="Puno"><option value="Arequipa"></datalist>
                </label>
                <label class="f">Categoría
                    <input type="text" name="categoria" list="dl-cats" value="{{ $v('categoria', $tour->categoria) }}" maxlength="100" placeholder="Cultural, Trekking…">
                    <datalist id="dl-cats"><option value="Cultural"><option value="Trekking"><option value="Aventura"><option value="Místico"></datalist>
                </label>
                <label class="f">Dificultad
                    <select name="nivel">
                        <option value="">—</option>
                        @foreach(['Baixo' => 'Baja', 'Médio' => 'Media', 'Alto' => 'Alta'] as $val => $lbl)
                            <option value="{{ $val }}" @selected($v('nivel', $tour->nivel) === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="f">Tamaño del grupo
                    <input type="number" name="tamano_grupo" min="1" max="500" value="{{ $v('tamano_grupo', $tour->tamano_grupo) }}" placeholder="Máx. personas">
                </label>
                <label class="f">Etiqueta en la tarjeta
                    <select name="badge">
                        <option value="">Ninguna</option>
                        @foreach(['Mais vendido' => 'Más vendido', 'Novo' => 'Nuevo', 'Oferta' => 'Oferta'] as $val => $lbl)
                            <option value="{{ $val }}" @selected($v('badge', $tour->badge) === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>

        <div class="card-f">
            <h3>Descripción</h3>
            <p class="sub">Qué verá el viajero al abrir el tour. Los saltos de línea se respetan. Este es el texto en el idioma base del sitio.</p>
            <label class="f"><span class="sr-only">Descripción</span>
                <textarea name="descripcion" rows="7" placeholder="Describe la experiencia, lo que hace especial al tour…">{{ $v('descripcion', $tour->descripcion) }}</textarea>
            </label>
        </div>

        <div class="card-f">
            <h3>Imágenes</h3>
            <p class="sub">Hasta {{ $maxImagenes }} imágenes, de máximo {{ $maxKb }} KB cada una (JPG, PNG o WebP). La primera es la portada. Si una pesa más, la comprimimos automáticamente.</p>
            @include('admin.partials.uploader', ['orden' => 'imagenes_orden[]', 'archivos' => 'imagenes_nuevas[]', 'max' => $maxImagenes, 'maxKb' => $maxKb, 'inicial' => $galeriaVista])
        </div>

        <div class="card-f">
            <h3>Qué incluye y qué no incluye</h3>
            <div class="two">
                <div class="col-ok">
                    <h4>✓ Incluye</h4>
                    <div class="rows" data-repeat data-next="{{ count($incluyeVista) }}">
                        @foreach($incluyeVista as $x)@include('admin.partials.lista-fila', ['name' => 'incluye_items[]', 'value' => $x, 'placeholder' => 'Ej: Guía profesional'])@endforeach
                        <template data-tpl>@include('admin.partials.lista-fila', ['name' => 'incluye_items[]', 'value' => '', 'placeholder' => 'Ej: Guía profesional'])</template>
                    </div>
                    <button type="button" class="btn-add" data-add>+ Agregar</button>
                </div>
                <div class="col-no">
                    <h4>✕ No incluye</h4>
                    <div class="rows" data-repeat data-next="{{ count($noIncluyeVista) }}">
                        @foreach($noIncluyeVista as $x)@include('admin.partials.lista-fila', ['name' => 'no_incluye_items[]', 'value' => $x, 'placeholder' => 'Ej: Almuerzo'])@endforeach
                        <template data-tpl>@include('admin.partials.lista-fila', ['name' => 'no_incluye_items[]', 'value' => '', 'placeholder' => 'Ej: Almuerzo'])</template>
                    </div>
                    <button type="button" class="btn-add" data-add>+ Agregar</button>
                </div>
            </div>
        </div>

        <div class="card-f">
            <h3>Adicionales</h3>
            <p class="sub">Servicios opcionales que el viajero puede sumar (entradas extra, upgrade de tren, caballo…). El precio es opcional.</p>
            <div class="rows" data-repeat data-next="{{ count($adicionalesVista) }}">
                @foreach($adicionalesVista as $i => $a)@include('admin.partials.adicional-fila', ['i' => $i, 'a' => $a])@endforeach
                <template data-tpl>@include('admin.partials.adicional-fila', ['i' => '__i__', 'a' => []])</template>
            </div>
            <button type="button" class="btn-add" data-add>+ Agregar adicional</button>
        </div>

        <div class="card-f">
            <h3>Video de YouTube</h3>
            <label class="f">Enlace del video <small>(opcional)</small>
                <input type="url" name="video_url" id="fVideo" value="{{ $v('video_url', $tour->video_url) }}" maxlength="255" placeholder="https://www.youtube.com/watch?v=…">
            </label>
            <div class="yt" id="ytPrev" hidden></div>
        </div>
    </section>

    {{-- ================= PANEL: ITINERARIO ================= --}}
    <section class="tab-pane" id="pane-itinerario" data-pane="itinerario" role="tabpanel" hidden>
        <div class="card-f">
            <h3>Itinerario</h3>
            <p class="sub">Agrega un paso por cada parada o día. Cada paso tiene título, descripción y hasta {{ $maxImagenesPaso }} imágenes. Usa ↑ ↓ para ordenarlos.</p>
            <div class="rows" data-repeat data-steps data-next="{{ count($pasosVista) }}">
                @foreach($pasosVista as $i => $p)@include('admin.partials.paso-fila', ['i' => $i, 'p' => $p, 'maxPaso' => $maxImagenesPaso, 'maxKb' => $maxKb])@endforeach
                <template data-tpl>@include('admin.partials.paso-fila', ['i' => '__i__', 'p' => [], 'maxPaso' => $maxImagenesPaso, 'maxKb' => $maxKb])</template>
            </div>
            <button type="button" class="btn-add" data-add>+ Agregar paso</button>
        </div>
    </section>

    {{-- ================= PANEL: PRECIO ================= --}}
    <section class="tab-pane" id="pane-precio" data-pane="precio" role="tabpanel" hidden>
        <div class="card-f">
            <h3>Precios (en dólares, US$)</h3>
            <p class="sub">Puedes manejar varios precios: por tipo de viajero, tamaño de grupo, categoría de servicio, etc. En el sitio se muestra «Desde» con el precio más bajo.</p>
            <div class="rows" data-repeat data-next="{{ count($preciosVista) }}">
                @foreach($preciosVista as $i => $p)@include('admin.partials.precio-fila', ['i' => $i, 'p' => $p])@endforeach
                <template data-tpl>@include('admin.partials.precio-fila', ['i' => '__i__', 'p' => []])</template>
            </div>
            <button type="button" class="btn-add" data-add>+ Agregar otro precio</button>
        </div>
        <div class="card-f">
            <h3>Descuento</h3>
            <label class="f">Descuento sobre todos los precios (%) <small>(opcional, se muestra tachado en el sitio)</small>
                <input type="number" name="descuento" min="0" max="90" value="{{ $v('descuento', $tour->descuento ?: '') }}" placeholder="0" style="max-width:160px">
            </label>
        </div>
    </section>

    {{-- ================= PANEL: PUBLICACIÓN ================= --}}
    <section class="tab-pane" id="pane-publicacion" data-pane="publicacion" role="tabpanel" hidden>
        <div class="card-f">
            <h3>¿Dónde aparece en el inicio?</h3>
            <p class="sub">Elige una o varias secciones de la página principal. Solo se muestran cuando el tour está publicado.
                <a href="{{ route('admin.secciones') }}" target="_blank" rel="noopener">Administrar secciones</a></p>
            <div class="check-row">
                @forelse($secciones as $s)
                    <label><input type="checkbox" name="secciones[]" value="{{ $s->id }}" @checked(in_array($s->id, $seccionesSel, true))><span>{{ $s->nombre }}</span></label>
                @empty
                    <p class="empty">Aún no hay secciones. Créalas en «Secciones del inicio».</p>
                @endforelse
            </div>
        </div>
        <div class="card-f">
            <h3>Experiencia destacada</h3>
            <p class="sub">El tour destacado se muestra en un bloque grande en el inicio (con sus fotos y lo que incluye). Si marcas varios, se muestra el más reciente.</p>
            <label class="check-line"><input type="checkbox" name="destacado" value="1" @checked(old('destacado', $tour->destacado))> <span>Mostrar como experiencia destacada en el inicio</span></label>
        </div>
        <div class="card-f">
            <h3>Búsqueda y Google (SEO)</h3>
            <label class="f">Descripción para buscadores <small>(máx. 300 caracteres)</small>
                <textarea name="meta_descripcion" rows="2" maxlength="300" placeholder="Resumen corto que sale bajo el título en Google">{{ $v('meta_descripcion', $tour->meta_descripcion) }}</textarea>
            </label>
            <label class="f">Palabras clave <small>(separadas por coma; también ayudan al buscador del sitio)</small>
                <input type="text" name="palabras_clave" value="{{ $v('palabras_clave', $tour->palabras_clave) }}" maxlength="255" placeholder="machu picchu, tren, ciudadela inca">
            </label>
        </div>
    </section>

    {{-- ================= PANEL: IDIOMAS ================= --}}
    <section class="tab-pane" id="pane-idiomas" data-pane="idiomas" role="tabpanel" hidden>
        @foreach(['es' => 'Español', 'en' => 'English'] as $lang => $nombreLang)
            <div class="card-f">
                <h3>{{ $nombreLang }}</h3>
                @if($loop->first)<p class="sub">Opcional. Si dejas un campo vacío, el sitio muestra el texto del idioma base (panel «Tour»).</p>@endif
                <div class="fgrid">
                    <label class="f">Nombre<input type="text" name="traducciones[{{ $lang }}][nombre]" value="{{ $tr($lang, 'nombre') }}" maxlength="150"></label>
                    <label class="f">Duración<input type="text" name="traducciones[{{ $lang }}][duracion]" value="{{ $tr($lang, 'duracion') }}" maxlength="100"></label>
                </div>
                <label class="f">Descripción<textarea name="traducciones[{{ $lang }}][descripcion]" rows="4">{{ $tr($lang, 'descripcion') }}</textarea></label>
                <div class="two">
                    <label class="f">Incluye <small>(una línea por elemento)</small><textarea name="traducciones[{{ $lang }}][incluye]" rows="4">{{ $tr($lang, 'incluye') }}</textarea></label>
                    <label class="f">No incluye <small>(una línea por elemento)</small><textarea name="traducciones[{{ $lang }}][no_incluye]" rows="4">{{ $tr($lang, 'no_incluye') }}</textarea></label>
                </div>
            </div>
        @endforeach
    </section>

    {{-- ================= Barra de acciones ================= --}}
    <div class="actionbar">
        <span class="status">Estado: <b>{{ $estadoTxt }}</b>
            @if($editando) · <a href="{{ route('admin.tours.preview', $tour->id) }}" target="_blank" rel="noopener">Vista previa</a>@endif</span>
        <a class="btn-ghost" href="{{ route('admin.tours') }}">Cancelar</a>
        <button type="submit" name="accion" value="borrador" class="btn-draft" id="btnBorrador">Guardar borrador</button>
        <button type="submit" name="accion" value="publicar" class="btn-admin" id="btnPublicar">{{ $editando && $tour->estado === 'publicado' ? 'Guardar cambios' : 'Publicar tour' }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-tour.js') }}"></script>
@endpush
