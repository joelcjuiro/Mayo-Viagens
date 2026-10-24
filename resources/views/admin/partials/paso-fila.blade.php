<div class="step" data-row>
    <div class="step-head">
        <span class="step-num" data-num>1</span>
        <span class="grow">Paso del itinerario</span>
        <button type="button" class="mini" data-move="-1" aria-label="Subir">↑</button>
        <button type="button" class="mini" data-move="1" aria-label="Bajar">↓</button>
        <button type="button" class="btn-x" data-del aria-label="Quitar paso">×</button>
    </div>
    <div class="fgrid">
        <label class="f">Título
            <input type="text" name="itinerario[{{ $i }}][titulo]" value="{{ $p['titulo'] ?? '' }}" maxlength="150" placeholder="Ej: Visita a la ciudadela">
        </label>
        <label class="f">Horario o día <small>(opcional)</small>
            <input type="text" name="itinerario[{{ $i }}][horario]" value="{{ $p['horario'] ?? '' }}" maxlength="60" placeholder="Ej: 06:00 · Día 2 · Mañana">
        </label>
    </div>
    <label class="f">Descripción
        <textarea name="itinerario[{{ $i }}][descripcion]" rows="3" maxlength="5000" placeholder="Qué se hace en este paso">{{ $p['descripcion'] ?? '' }}</textarea>
    </label>
    <details class="trad">
        <summary>Traducciones de este paso (opcional)</summary>
        @foreach(['pt' => 'Português', 'es' => 'Español', 'en' => 'English'] as $l => $ln)
            <label class="f">Título · {{ $ln }}
                <input type="text" name="itinerario[{{ $i }}][tr][{{ $l }}][titulo]" value="{{ $p['tr'][$l]['titulo'] ?? '' }}" maxlength="150">
            </label>
            <label class="f">Descripción · {{ $ln }}
                <textarea name="itinerario[{{ $i }}][tr][{{ $l }}][descripcion]" rows="2" maxlength="5000">{{ $p['tr'][$l]['descripcion'] ?? '' }}</textarea>
            </label>
        @endforeach
    </details>
    <div class="f">Imágenes del paso <small>(máx. {{ $maxPaso }}, {{ $maxKb }} KB cada una)</small>
        @include('admin.partials.uploader', [
            'orden' => "itinerario[$i][imagenes_orden][]",
            'archivos' => "itinerario[$i][imagenes_nuevas][]",
            'max' => $maxPaso, 'maxKb' => $maxKb,
            'inicial' => collect($p['imagenes'] ?? [])->map(fn ($r) => ['ruta' => $r, 'url' => \App\Models\Tour::urlDe($r)])->all(),
        ])
    </div>
</div>
