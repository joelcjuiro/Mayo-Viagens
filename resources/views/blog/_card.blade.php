@php $f = $a->fecha(); @endphp
<article class="bl-card">
    <a class="bl-media" href="{{ $a->url }}" tabindex="-1" aria-hidden="true">
        @if($a->miniatura_url)<img src="{{ $a->miniatura_url }}" alt="{{ $a->imagen_alt }}" width="640" height="400" loading="lazy" decoding="async" onerror="this.remove()">@endif
    </a>
    <div class="bl-body">
        <p class="bl-meta">
            <span class="bl-cat bl-cat-{{ $a->categoria }}">{{ __('blog.cats.'.$a->categoria) }}</span>
            <time datetime="{{ $f->toDateString() }}">{{ $f->locale(app()->getLocale())->translatedFormat('j M Y') }}</time>
            <span>· {{ $a->minutos() }} {{ __('blog.min') }}</span>
        </p>
        <h3><a href="{{ $a->url }}">{{ $a->t('titulo') }}</a></h3>
        <p>{{ $a->extracto(140) }}</p>
    </div>
</article>
