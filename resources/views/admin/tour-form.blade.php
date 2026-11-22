@extends('layouts.admin')
@php
    $editando = $tour->exists;
    $estadoTxt = ! $editando ? 'Nuevo' : ['publicado' => 'Publicado', 'borrador' => 'Borrador', 'oculto' => 'Oculto'][$tour->estado];
@endphp
@section('title', $editando ? 'Editar tour' : 'Nuevo tour')
@section('heading', $editando ? 'Editar: '.\Illuminate\Support\Str::limit($tour->nombre, 40) : 'Nuevo tour')
@section('subheading', 'Completa los paneles. Puedes guardar como borrador en cualquier momento.')

@push('styles')<link rel="stylesheet" href="{{ \App\Support\Media::v('css/admin-tour.css') }}">@endpush

@section('content')
@php
    // Valores: lo que se escribió (si hubo errores) o lo guardado
    $v = fn ($k, $d = null) => old($k, $d);
    $idiomaBase = in_array(old('idioma_base', $tour->idioma_base), ['pt', 'es', 'en'], true) ? old('idioma_base', $tour->idioma_base) : 'es';
    $nombresIdioma = ['es' => 'Español', 'pt' => 'Português', 'en' => 'English'];
    $sugerencias = [
        'ok' => [
            'es' => ['Guía profesional', 'Transporte turístico', 'Entradas', 'Desayuno', 'Almuerzo', 'Recojo en el hotel', 'Seguro de viaje'],
            'pt' => ['Guia profissional', 'Transporte turístico', 'Ingressos', 'Café da manhã', 'Almoço', 'Busca no hotel', 'Seguro viagem'],
            'en' => ['Professional guide', 'Tourist transport', 'Entrance tickets', 'Breakfast', 'Lunch', 'Hotel pick-up', 'Travel insurance'],
        ],
        'no' => [
            'es' => ['Almuerzo', 'Propinas', 'Entradas', 'Gastos personales', 'Bebidas', 'Seguro de viaje'],
            'pt' => ['Almoço', 'Gorjetas', 'Ingressos', 'Gastos pessoais', 'Bebidas', 'Seguro viagem'],
            'en' => ['Lunch', 'Tips', 'Entrance tickets', 'Personal expenses', 'Drinks', 'Travel insurance'],
        ],
    ];

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
    $pasosConTexto = collect($pasosVista)->filter(fn ($p) => filled($p['titulo'] ?? null) || filled($p['descripcion'] ?? null));

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
        <div class="card-f lang-base">
            <h3>¿En qué idioma estás escribiendo este tour?</h3>
            <p class="sub">Marca el idioma. En el panel «Idiomas» solo te pediremos los que faltan, y el texto que escribas aquí se muestra tal cual a quien navega en ese idioma.</p>
            <div class="lang-pills" role="radiogroup" aria-label="Idioma base">
                @foreach($nombresIdioma as $cod => $nom)
                    <label class="lang-pill"><input type="radio" name="idioma_base" value="{{ $cod }}" @checked($idiomaBase === $cod)><span><b>{{ strtoupper($cod) }}</b> {{ $nom }}</span></label>
                @endforeach
            </div>
        </div>

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
            <p class="sub">Qué verá el viajero al abrir el tour. Usa la barra para poner <b>negritas</b>, <i>cursivas</i>, subtítulos y listas: lo importante se destaca al publicar. Los saltos de línea se respetan.</p>
            <label class="f"><span class="sr-only">Descripción</span>
                @include('admin.partials.md-bar')
                <textarea name="descripcion" rows="9" placeholder="Describe la experiencia, lo que hace especial al tour…">{{ $v('descripcion', $tour->descripcion) }}</textarea>
            </label>
        </div>

        <div class="card-f">
            <h3>Imágenes</h3>
            <p class="sub">Hasta {{ $maxImagenes }} imágenes, de máximo {{ $maxKb }} KB cada una (JPG, PNG o WebP). La primera es la portada. Si una pesa más, la comprimimos automáticamente.</p>
            @include('admin.partials.uploader', ['orden' => 'imagenes_orden[]', 'archivos' => 'imagenes_nuevas[]', 'max' => $maxImagenes, 'maxKb' => $maxKb, 'inicial' => $galeriaVista])
        </div>

        <div class="card-f inc-card">
            <h3>Qué incluye y qué no incluye</h3>
            <p class="sub">Toca una sugerencia para agregarla al instante o escribe la tuya. En la página del tour se ven como tarjetas verdes (incluye) y rojas (no incluye).</p>
            <div class="inc-grid">
                @foreach(['ok' => ['Incluye', 'Lo que recibe el viajero', '✓', 'incluye_items[]', $incluyeVista, 'Ej: Guía profesional'], 'no' => ['No incluye', 'Lo que paga aparte', '✕', 'no_incluye_items[]', $noIncluyeVista, 'Ej: Almuerzo']] as $tipo => [$titulo, $leyenda, $icono, $campo, $lista, $ph])
                <div class="inc-col {{ $tipo }}">
                    <div class="inc-head"><span class="inc-ico" aria-hidden="true">{{ $icono }}</span><div><h4>{{ $titulo }}</h4><small>{{ $leyenda }}</small></div><b class="inc-count" data-inc-count title="Elementos">0</b></div>
                    <div class="chips-sug" aria-label="Sugerencias">
                        @foreach($sugerencias[$tipo] as $cod => $items)
                            @foreach($items as $sg)<button type="button" class="chip-sug" data-suggest="{{ $sg }}" data-sug-lang="{{ $cod }}" @if($cod !== $idiomaBase) hidden @endif>+ {{ $sg }}</button>@endforeach
                        @endforeach
                    </div>
                    <div class="rows" data-repeat data-next="{{ count($lista) }}">
                        @foreach($lista as $x)@include('admin.partials.lista-fila', ['name' => $campo, 'value' => $x, 'placeholder' => $ph])@endforeach
                        <template data-tpl>@include('admin.partials.lista-fila', ['name' => $campo, 'value' => '', 'placeholder' => $ph])</template>
                    </div>
                    <button type="button" class="btn-add" data-add>+ Agregar</button>
                </div>
                @endforeach
            </div>
        </div>

        <div class="card-f">
            <h3>Encuentro y recogida</h3>
            <p class="sub">Dónde y a qué hora se encuentran con el guía o los recogen (hotel, plaza, estación). Se muestra en la página del tour con su propio apartado.</p>
            <label class="f"><span class="sr-only">Encuentro y recogida</span>
                @include('admin.partials.md-bar')
                <textarea name="encuentro" rows="5" placeholder="Ej: **Recojo en tu hotel** entre las 5:30 y 6:00 a.m. (centro de Cusco). Si te alojas fuera del centro, nos encontramos en la **Plaza de Armas**.">{{ $v('encuentro', $tour->encuentro) }}</textarea>
            </label>
        </div>

        <div class="card-f">
            <h3>Información importante</h3>
            <p class="sub">Lo que el viajero debe saber antes de reservar: qué llevar, altura, restricciones, políticas, documentos…</p>
            <label class="f"><span class="sr-only">Información importante</span>
                @include('admin.partials.md-bar')
                <textarea name="info_importante" rows="7" placeholder="- Lleva **pasaporte original**.&#10;- Ropa abrigada y protector solar.&#10;- No apto para personas con problemas cardíacos graves.">{{ $v('info_importante', $tour->info_importante) }}</textarea>
            </label>
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
            <p class="hint-s"><b>Cómo se calcula el total en la página del tour:</b> el viajero elige fecha y número de personas (2 por defecto) y se multiplica el <b>primer precio de la lista</b> por esa cantidad. Con 3 personas se suma una persona más al precio de 2. Pon primero el precio «por persona».</p>
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
        <div class="card-f" id="autoTrad" data-url="{{ route('admin.tours.traducir') }}">
            <h3>Traducción automática</h3>
            <p class="sub">Escribiste el tour en <b data-base-name>{{ $nombresIdioma[$idiomaBase] }}</b> (puedes cambiarlo en el panel «Tour»). Traduce con un clic a <b data-missing-names>{{ collect($nombresIdioma)->except($idiomaBase)->implode(' y ') }}</b>: nombre, duración, descripción, encuentro, información importante, incluye / no incluye, pasos del itinerario y precios. Después revisa cada texto.</p>
            <label class="check-line"><input type="checkbox" id="trOver"> <span>Reemplazar traducciones que ya escribí</span></label>
            <div>
                <button type="button" class="btn-admin" id="trBtn" @disabled(! \App\Support\Traductor::disponible())>✦ Traducir a los idiomas que faltan</button>
                <span class="up-msg" id="trMsg" role="status"></span>
            </div>
            @unless(\App\Support\Traductor::disponible())
                <p class="hint-s">La traducción automática aún no está activada: falta la clave <code>TRADUCTOR_KEY</code> en el archivo <code>.env</code> (pasos en CAMBIOS-TOURS.md). Mientras tanto puedes escribir las traducciones a mano.</p>
            @endunless
        </div>

        @foreach($nombresIdioma as $lang => $nombreLang)
            <div @class(['card-f', 'lang-off' => $lang === $idiomaBase]) data-lang-card="{{ $lang }}">
                <h3>{{ $nombreLang }} <small class="lang-tag">traducción</small></h3>
                @if($loop->first)<p class="sub">Opcional. Si dejas un campo vacío, el sitio muestra el texto original del tour. El idioma en que escribiste el tour no aparece aquí porque no necesita traducción.</p>@endif
                <div class="fgrid">
                    <label class="f">Nombre<input type="text" name="traducciones[{{ $lang }}][nombre]" value="{{ $tr($lang, 'nombre') }}" maxlength="150"></label>
                    <label class="f">Duración<input type="text" name="traducciones[{{ $lang }}][duracion]" value="{{ $tr($lang, 'duracion') }}" maxlength="100"></label>
                </div>
                <label class="f">Descripción
                    @include('admin.partials.md-bar')
                    <textarea name="traducciones[{{ $lang }}][descripcion]" rows="5">{{ $tr($lang, 'descripcion') }}</textarea></label>
                <label class="f">Encuentro y recogida
                    @include('admin.partials.md-bar')
                    <textarea name="traducciones[{{ $lang }}][encuentro]" rows="3">{{ $tr($lang, 'encuentro') }}</textarea></label>
                <label class="f">Información importante
                    @include('admin.partials.md-bar')
                    <textarea name="traducciones[{{ $lang }}][info_importante]" rows="4">{{ $tr($lang, 'info_importante') }}</textarea></label>
                <div class="two">
                    <label class="f">Incluye <small>(una línea por elemento)</small><textarea name="traducciones[{{ $lang }}][incluye]" rows="4">{{ $tr($lang, 'incluye') }}</textarea></label>
                    <label class="f">No incluye <small>(una línea por elemento)</small><textarea name="traducciones[{{ $lang }}][no_incluye]" rows="4">{{ $tr($lang, 'no_incluye') }}</textarea></label>
                </div>

                <div class="tr-itin" data-tr-itin="{{ $lang }}">
                    <h4>Itinerario en {{ $nombreLang }}</h4>
                    <p class="hint-s" data-tr-empty @if($pasosConTexto->isNotEmpty()) hidden @endif>Aún no hay pasos en el itinerario. Agrégalos en el panel «Itinerario» y aparecerán aquí.</p>
                    <div class="tr-itin-list">
                        @foreach($pasosConTexto as $i => $p)@include('admin.partials.paso-tr', ['i' => $i, 'p' => $p, 'lang' => $lang])@endforeach
                    </div>
                    <template data-tr-step-tpl>@include('admin.partials.paso-tr', ['i' => '__i__', 'p' => [], 'lang' => $lang])</template>
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
<script src="{{ \App\Support\Media::v('js/admin-tour.js') }}"></script>
<script src="{{ \App\Support\Media::v('js/admin-tour-extra.js') }}"></script>
@endpush
