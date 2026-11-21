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
    // Precio por persona que se multiplica por el número de viajeros: el primer precio de la lista
    $unit = optional($precios->first(fn ($p) => $p->precio > 0))->precio ?? ($tour->precio > 0 ? (float) $tour->precio : null);
    $fmt = fn ($h) => \App\Support\Formato::html($h);
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
                    <div class="main"><img src="{{ $galeria[0] }}" alt="{{ $nombre }}" data-gallery-main fetchpriority="high" decoding="async"></div>
                    <div class="t-thumbs">
                        @foreach($galeria as $i => $g)
                            <button type="button" data-gallery-src="{{ $g }}" aria-label="{{ $nombre }} {{ $i + 1 }}" @if($i === 0) aria-current="true" @endif><img src="{{ $g }}" alt="" loading="lazy"></button>
                        @endforeach
                    </div>
                </div>
            @endif
            <h2>{{ __('site.detail.about') }}</h2>
            <div class="rich">{!! $fmt($tour->t('descripcion')) !!}</div>

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
                                @if($paso->t('descripcion'))<div class="rich">{!! $fmt($paso->t('descripcion')) !!}</div>@endif
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
                <div class="detail-block tour-inc">
                    @if($incluye)
                        <section class="inc-box ok">
                            <h3><span class="inc-ico" aria-hidden="true">✓</span>{{ __('site.detail.includes') }}</h3>
                            <ul>@foreach($incluye as $item)<li><span class="mk" aria-hidden="true">✓</span>{{ $item }}</li>@endforeach</ul>
                        </section>
                    @endif
                    @if($noIncluye)
                        <section class="inc-box no">
                            <h3><span class="inc-ico" aria-hidden="true">✕</span>{{ __('site.detail.excludes') }}</h3>
                            <ul>@foreach($noIncluye as $item)<li><span class="mk" aria-hidden="true">✕</span>{{ $item }}</li>@endforeach</ul>
                        </section>
                    @endif
                </div>
            @endif

            @if(filled($tour->t('encuentro')))
                <div class="detail-block tour-note meet">
                    <h3><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ __('site.detail.meeting') }}</h3>
                    <div class="rich">{!! $fmt($tour->t('encuentro')) !!}</div>
                </div>
            @endif

            @if(filled($tour->t('info_importante')))
                <div class="detail-block tour-note imp">
                    <h3><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>{{ __('site.detail.important') }}</h3>
                    <div class="rich">{!! $fmt($tour->t('info_importante')) !!}</div>
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
                        @include('partials.fotos', ['galeria' => $o->fotosGaleria(), 'grupo' => 'tour'.$o->id])
                    </article>
                @empty
                    <p class="meta">{{ __('resenas.tour_none') }}</p>
                @endforelse
            </div>
        </div>

        <aside class="booking-box">
            @if($unit)
                @php $u = $final($unit); @endphp
                <div class="bk" data-booking data-unit="{{ $u }}" data-orig="{{ $dto > 0 ? $unit : '' }}" data-max="20"
                     data-one="{{ __('site.detail.traveler_one') }}" data-many="{{ __('site.detail.traveler_many') }}" data-total="{{ __('site.detail.total_for') }}"
                     data-wa="{{ __('site.detail.wa_details') }}" data-book="{{ route('reservas', ['tour' => $tour->id]) }}">
                    <p class="bk-from"><span>{{ __('site.detail.from') }}</span>
                        @if($dto > 0)<s class="price-old">US$ {{ number_format($unit, 0) }}</s>@endif
                        <b>US$ {{ number_format($u, 0) }}</b> <span>{{ __('site.detail.per_person') }}</span></p>
                    <div class="bk-fields">
                        <label class="bk-field"><span>{{ __('site.detail.date') }}</span>
                            <input type="date" data-bk-date value="{{ now()->addDays(7)->toDateString() }}" min="{{ now()->addDay()->toDateString() }}" required>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/></svg></label>
                        <div class="bk-field"><span>{{ __('site.detail.travelers') }}</span>
                            <div class="bk-step">
                                <button type="button" data-bk-minus aria-label="−">−</button>
                                <output data-bk-count aria-live="polite">2</output>
                                <button type="button" data-bk-plus aria-label="+">+</button>
                            </div>
                            <small class="bk-people-label" data-bk-people-label></small></div>
                    </div>
                    <div class="bk-total">
                        <div><small data-bk-total-label></small><strong data-bk-total></strong></div>
                        <span class="bk-calc" data-bk-calc></span>
                    </div>
                    <a href="{{ route('reservas', ['tour' => $tour->id]) }}" class="btn btn-gold btn-block" data-bk-link>{{ __('site.detail.book') }}</a>
                    <small class="bk-note" data-bk-note hidden>{{ __('site.detail.group_note') }}</small>
                </div>
            @elseif($desde)
                <p class="price"><small>{{ __('site.detail.from') }}</small>
                    @if($dto > 0)<s class="price-old">US$ {{ number_format($desde, 0) }}</s>@endif
                    US$ {{ number_format($final($desde), 0) }} <small>{{ __('site.detail.per_person') }}</small></p>
            @endif
            @if($precios->count() > 1)
                <p class="bk-rates">{{ __('site.detail.rates') }}</p>
                <div class="t-prices">
                    @foreach($precios as $pr)
                        <div class="t-price"><div>{{ $pr->t('etiqueta') }}@if($pr->t('descripcion'))<small>{{ $pr->t('descripcion') }}</small>@endif</div><b>US$ {{ number_format($final($pr->precio), 0) }}</b></div>
                    @endforeach
                </div>
            @elseif($precios->count() === 1 && $precios->first()->t('descripcion'))
                <p class="meta">{{ $precios->first()->t('descripcion') }}</p>
            @endif
            @if($tour->duracion)<p class="meta">{{ __('site.detail.duration') }}: {{ $tour->t('duracion') }}</p>@endif
            @unless($unit)<a href="{{ route('reservas', ['tour' => $tour->id]) }}" class="btn btn-gold btn-block">{{ __('site.detail.book') }}</a>@endunless
            <a href="https://wa.me/{{ $whats }}?text={{ urlencode(__('site.detail.wa_text').$nombre) }}" data-bk-wa data-wa-base="{{ urlencode(__('site.detail.wa_text').$nombre) }}" target="_blank" rel="noopener" class="btn btn-outline btn-block">{{ __('site.detail.ask_whatsapp') }}</a>
            <ul class="guarantees">
                @foreach(__('site.detail.guarantees') as $g)<li>{{ $g }}</li>@endforeach
            </ul>
        </aside>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    var box = document.querySelector('[data-booking]');
    if (!box) { return; }
    var lang = document.documentElement.lang || 'es';
    var fmt = new Intl.NumberFormat(lang, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    var unit = parseFloat(box.dataset.unit), orig = parseFloat(box.dataset.orig) || 0, max = parseInt(box.dataset.max, 10) || 20;
    var n = 2;
    var q = function (s) { return box.querySelector(s); };
    var dateEl = q('[data-bk-date]'), cnt = q('[data-bk-count]'), link = q('[data-bk-link]');
    var wa = document.querySelector('[data-bk-wa]');

    function pintar() {
        var total = Math.round(unit * n * 100) / 100;
        var palabra = n === 1 ? box.dataset.one : box.dataset.many;
        cnt.textContent = n;
        q('[data-bk-people-label]').textContent = palabra;
        q('[data-bk-total-label]').textContent = box.dataset.total.replace(':n', n + ' ' + palabra);
        q('[data-bk-total]').innerHTML = (orig ? '<s>US$ ' + fmt.format(Math.round(orig * n * 100) / 100) + '</s> ' : '') + 'US$ ' + fmt.format(total);
        q('[data-bk-calc]').textContent = n + ' × US$ ' + fmt.format(unit);
        q('[data-bk-minus]').disabled = n <= 1;
        q('[data-bk-plus]').disabled = n >= max;
        q('[data-bk-note]').hidden = n < max;
        var fecha = dateEl.value || '';
        link.href = box.dataset.book + (box.dataset.book.indexOf('?') > -1 ? '&' : '?') + 'personas=' + n + (fecha ? '&fecha=' + encodeURIComponent(fecha) : '');
        if (wa) { wa.href = wa.href.split('?')[0] + '?text=' + wa.dataset.waBase + encodeURIComponent(box.dataset.wa.replace(':n', n + ' ' + palabra).replace(':date', fecha || '—')); }
    }
    q('[data-bk-minus]').addEventListener('click', function () { if (n > 1) { n--; pintar(); } });
    q('[data-bk-plus]').addEventListener('click', function () { if (n < max) { n++; pintar(); } });
    dateEl.addEventListener('change', pintar);
    pintar();
})();
</script>
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
