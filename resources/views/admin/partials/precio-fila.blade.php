<div class="price-row" data-row>
    <label class="f">Nombre del precio
        <input type="text" name="precios[{{ $i }}][etiqueta]" value="{{ $p['etiqueta'] ?? '' }}" maxlength="120" placeholder="Ej: Adulto, Niño, Grupo privado 2–4">
    </label>
    <label class="f">Precio
        <span class="usd"><b>US$</b><input type="number" name="precios[{{ $i }}][precio]" value="{{ $p['precio'] ?? '' }}" min="0" step="0.01" placeholder="0.00"></span>
    </label>
    <label class="f">¿Qué más incluye este precio?
        <textarea name="precios[{{ $i }}][descripcion]" rows="2" maxlength="1000" placeholder="Ej: Incluye almuerzo buffet y recojo en el hotel">{{ $p['descripcion'] ?? '' }}</textarea>
    </label>
    <button type="button" class="btn-x" data-del aria-label="Quitar precio">×</button>
    <details class="trad" style="grid-column:1/-1">
        <summary>Traducciones de este precio (opcional)</summary>
        <div class="fgrid">
        @foreach(['es' => 'Español', 'en' => 'English'] as $l => $ln)
            <label class="f">Nombre · {{ $ln }}
                <input type="text" name="precios[{{ $i }}][tr][{{ $l }}][etiqueta]" value="{{ $p['tr'][$l]['etiqueta'] ?? '' }}" maxlength="120">
            </label>
            <label class="f">Qué incluye · {{ $ln }}
                <textarea name="precios[{{ $i }}][tr][{{ $l }}][descripcion]" rows="2" maxlength="1000">{{ $p['tr'][$l]['descripcion'] ?? '' }}</textarea>
            </label>
        @endforeach
        </div>
    </details>
</div>
