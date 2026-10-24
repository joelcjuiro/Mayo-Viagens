{{-- Subida de imágenes: @include('admin.partials.uploader', ['orden' => 'imagenes_orden[]', 'archivos' => 'imagenes_nuevas[]', 'max' => 7, 'inicial' => [['ruta'=>..,'url'=>..]], 'maxKb' => 300]) --}}
<div class="up" data-up data-max="{{ $max }}" data-max-kb="{{ $maxKb }}"
     data-order-name="{{ $orden }}" data-file-name="{{ $archivos }}"
     data-initial="{{ json_encode(array_values($inicial), JSON_UNESCAPED_SLASHES) }}">
    <div class="up-grid" data-up-grid></div>
    <input type="file" accept="image/jpeg,image/png,image/webp" multiple hidden data-up-pick>
    <input type="file" name="{{ $archivos }}" multiple hidden data-up-files>
    <span data-up-orders></span>
    <p class="up-count" data-up-count></p>
    <p class="up-msg" data-up-msg role="status"></p>
</div>
