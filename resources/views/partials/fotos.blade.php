{{-- Fotos de una opinión: miniaturas livianas; la foto grande se descarga solo al abrirla (lightbox). --}}
@if(! empty($galeria))
    <div class="op-photos">
        @foreach($galeria as $f)
            <a href="{{ $f['full'] }}" data-lightbox="{{ $grupo ?? 'fotos' }}" target="_blank" rel="noopener" aria-label="{{ __('blog.view_photo') }}">
                <img src="{{ $f['thumb'] }}" alt="" width="96" height="72" loading="lazy" decoding="async">
            </a>
        @endforeach
    </div>
@endif
