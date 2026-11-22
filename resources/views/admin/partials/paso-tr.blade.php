{{-- Traducción de un paso del itinerario a un idioma (panel Idiomas) --}}
<div class="tr-step" data-tr-step="{{ $i }}">
    <p class="tr-step-t"><span class="step-num" data-tr-num>{{ is_numeric($i) ? $i + 1 : '·' }}</span> <b data-tr-title>{{ ($p['titulo'] ?? '') !== '' ? $p['titulo'] : 'Paso del itinerario' }}</b></p>
    <label class="f">Título
        <input type="text" name="itinerario[{{ $i }}][tr][{{ $lang }}][titulo]" value="{{ $p['tr'][$lang]['titulo'] ?? '' }}" maxlength="150">
    </label>
    <label class="f">Descripción
        @include('admin.partials.md-bar')
        <textarea name="itinerario[{{ $i }}][tr][{{ $lang }}][descripcion]" rows="3" maxlength="5000">{{ $p['tr'][$lang]['descripcion'] ?? '' }}</textarea>
    </label>
</div>
