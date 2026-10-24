<div class="rowx">
    <input type="text" name="adicionales[{{ $i }}][nombre]" value="{{ $a['nombre'] ?? '' }}" maxlength="120" placeholder="Ej: Entrada a Huayna Picchu" aria-label="Nombre del adicional">
    <span class="usd w-s"><b>US$</b><input type="number" name="adicionales[{{ $i }}][precio]" value="{{ $a['precio'] ?? '' }}" min="0" step="0.01" placeholder="0" aria-label="Precio del adicional"></span>
    <button type="button" class="btn-x" data-del aria-label="Quitar">×</button>
</div>
