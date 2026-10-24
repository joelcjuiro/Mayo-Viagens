@extends('layouts.app')
@section('title', $tour->t('nombre').' | Mayo Viagens')
@section('description', $tour->meta_descripcion ?: $tour->resumen)
@push('styles')<link rel="stylesheet" href="{{ asset('css/tour-extra.css') }}">@endpush
@section('keywords', $tour->palabras_clave)
@section('og_image', $tour->imagen_url)

@php
    $lineas = fn ($t) => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $t))));
    $whats = config('mayo.whatsapp');
    $nombre = $tour->t('nombre');
    $itinerario = $lineas($tour->t('itinerario'));
    $incluye = $lineas($tour->t('incluye'));
    $noIncluye = $lineas($tour->t('no_incluye'));
    $pasos = $tour->itinerarioItems;
    $precios = $tour->precios;
    $adicionales = $tour->adicionales ?: [];
    $galeria = $tour->galeria_urls;
    $desde = $precios->where('precio', '>', 0)->min('precio') ?? ($tour->precio > 0 ? $tour->precio : null);
    $dto = (int) $tour->descuento;
    $final = fn ($v) => round($v * (100 - $dto) / 100, 2);
@endphp

@section('content')
@if(!empty($vistaPrevia))<div class="preview-bar">Vista previa · estado: {{ $tour->estado }} · {{ $tour->estado === 'publicado' ? 'visible en el sitio' : 'NO visible para los viajeros' }}</div>@endif
<section class="tour-hero" style="--hero-img:url('{{ $tour->imagen_url }}')">
    <div class="container">
        <p class="script script-gold">{{ __('site.detail.place') }}</p>
        <h1>{{ $nombre }}</h1>
        <p class="lead">{{ $tour->resumen }}</p>
    </div>
</section>

<section class="tour-detail">
    <div class="container detail-grid">
        <div class="prose">
            @if(count($galeria) > 1)
                <div class="t-gallery" data-gallery>
                    <div class="main"><img src="{{ $galeria[0] }}" alt="{{ $nombre }}" data-gallery-main></div>
                    <div class="t-thumbs">
                        @foreach($galeria as $i => $g)
                            <button type="button" data-gallery-src="{{ $g }}" aria-label="{{ $nombre }} {{ $i + 1 }}" @if($i === 0) aria-current="true" @endif><img src="{{ $g }}" alt="" loading="lazy"></button>
                        @endforeach
                    </div>
                </div>
            @endif
            <h2>{{ __('site.detail.about') }}</h2>
            {!! nl2br(e($tour->t('descripcion'))) !!}

            @if($tour->video_id)
                <div class="t-video"><iframe src="https://www.youtube-nocookie.com/embed/{{ $tour->video_id }}" title="{{ $nombre }}" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
            @endif

            @if($pasos->isNotEmpty())
                <div class="detail-block">
                    <h3>{{ __('site.detail.itinerary') }}</h3>
                    <div class="t-steps">
                        @foreach($pasos as $paso)
                            <div class="t-step">
                                @if($paso->horario)<span class="when">{{ $paso->horario }}</span>@endif
                                <h4>{{ $paso->t('titulo') }}</h4>
                                @if($paso->t('descripcion'))<p>{!! nl2br(e($paso->t('descripcion'))) !!}</p>@endif
                                @if($paso->imagenes)
                                    <div class="t-step-imgs">@foreach($paso->imagenes as $im)<img src="{{ \App\Models\Tour::urlDe($im) }}" alt="{{ $paso->t('titulo') }}" loading="lazy">@endforeach</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif($itinerario)
                <div class="detail-block">
                    <h3>{{ __('site.detail.itinerary') }}</h3>
                    <ul class="checklist">
                        @foreach($itinerario as $item)<li>{{ $item }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @if($incluye || $noIncluye)
                <div class="detail-block two-col">
                    @if($incluye)
                        <div>
                            <h3>{{ __('site.detail.includes') }}</h3>
                            <ul class="checklist">
                                @foreach($incluye as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        </div>
                    @endif
                    @if($noIncluye)
                        <div>
                            <h3>{{ __('site.detail.excludes') }}</h3>
                            <ul class="checklist no">
                                @foreach($noIncluye as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            @if($adicionales)
                <div class="detail-block">
                    <h3>{{ __('site.detail.extras') }}</h3>
                    <ul class="t-extras">
                        @foreach($adicionales as $a)<li><span>{{ $a['nombre'] }}</span>@if(!empty($a['precio']))<b>+ US$ {{ number_format($a['precio'], 0) }}</b>@endif</li>@endforeach
                    </ul>
                </div>
            @endif

            @php
                $opsTour = rescue(fn () => $tour->resenas()->aprobadas()->latest()->take(4)->get(), collect(), false);
                $opsProm = $opsTour->isNotEmpty() ? round((float) $tour->resenas()->aprobadas()->avg('calificacion'), 1) : null;
            @endphp
            <div class="detail-block tour-ops">
                <div class="tour-ops-head">
                    <h3>{{ __('resenas.tour_block') }}@if($opsProm) <small class="stars">★ {{ number_format($opsProm, 1) }}</small>@endif</h3>
                    <a class="btn btn-outline btn-sm" href="{{ route('opiniones', ['tour' => $tour->slug]) }}#opinar">{{ __('resenas.leave') }}</a>
                </div>
                @forelse($opsTour as $o)
                    <article class="op-card" style="margin-top:.8rem">
                        <header><span class="op-av">{{ mb_strtoupper(mb_substr($o->nombre, 0, 1)) }}</span>
                            <div><strong>{{ $o->nombre }}</strong><small>{{ $o->pais }}</small></div>
                            <span class="stars">{{ str_repeat('★', $o->calificacion) }}</span></header>
                        <p>{{ $o->comentario }}</p>
                    </article>
                @empty
                    <p class="meta">{{ __('resenas.tour_none') }}</p>
                @endforelse
            </div>
        </div>

        <aside class="booking-box">
            @if($desde)
                <p class="price"><small>{{ __('site.detail.from') }}</small>
                    @if($dto > 0)<s class="price-old">US$ {{ number_format($desde, 0) }}</s>@endif
                    US$ {{ number_format($final($desde), 0) }} <small>{{ __('site.detail.per_person') }}</small></p>
            @endif
            @if($precios->count() > 1)
                <div class="t-prices">
                    @foreach($precios as $pr)
                        <div class="t-price"><div>{{ $pr->t('etiqueta') }}@if($pr->t('descripcion'))<small>{{ $pr->t('descripcion') }}</small>@endif</div><b>US$ {{ number_format($final($pr->precio), 0) }}</b></div>
                    @endforeach
                </div>
            @elseif($precios->count() === 1 && $precios->first()->t('descripcion'))
                <p class="meta">{{ $precios->first()->t('descripcion') }}</p>
            @endif
            @if($tour->duracion)<p class="meta">{{ __('site.detail.duration') }}: {{ $tour->t('duracion') }}</p>@endif
            <a href="{{ route('reservas', ['tour' => $tour->id]) }}" class="btn btn-gold btn-block">{{ __('site.detail.book') }}</a>
            <a href="https://wa.me/{{ $whats }}?text={{ urlencode(__('site.detail.wa_text').$nombre) }}" target="_blank" rel="noopener" class="btn btn-outline btn-block">{{ __('site.detail.ask_whatsapp') }}</a>
            <ul class="guarantees">
                @foreach(__('site.detail.guarantees') as $g)<li>{{ $g }}</li>@endforeach
            </ul>
        </aside>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-gallery]').forEach(function (g) {
    var main = g.querySelector('[data-gallery-main]');
    g.querySelectorAll('[data-gallery-src]').forEach(function (b) {
        b.addEventListener('click', function () {
            main.src = b.dataset.gallerySrc;
            g.querySelectorAll('[data-gallery-src]').forEach(function (x) { x.removeAttribute('aria-current'); });
            b.setAttribute('aria-current', 'true');
        });
    });
});
</script>
@endpush
