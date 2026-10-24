@extends('layouts.app')

@section('title', __('site.meta.home_title'))

@php
    $stats = \App\Support\SiteStats::get();
    $whats = config('mayo.whatsapp');
    $reviews = config('mayo.reviews');
    // Opiniones enviadas por los viajeros y aprobadas en el panel (si hay, reemplazan a las del archivo de configuración)
    $opi = \App\Support\Opiniones::resumen();
    if ($opi['count'] > 0) {
        $reviews = $opi['items'];
        $stats['rating'] = $opi['avg'];
        $stats['rating_count'] = $opi['count'];
    }
    $featured = $tours->firstWhere('slug', 'machu-picchu') ?? $tours->first();
    $heroImg = file_exists(public_path('img/hero.jpg')) ? asset('img/hero.jpg') : null;
    $heroVideo = collect(['hero-video.mp4', 'pag web.mp4'])->first(fn ($f) => file_exists(public_path('img/'.$f)));
    $slides = collect(glob(public_path('img/tours/*')))
        ->filter(fn ($p) => preg_match('/\.(jpe?g|png|webp)$/i', $p) && str_contains((string) \Illuminate\Support\Str::of(pathinfo($p, PATHINFO_FILENAME))->ascii()->lower()->replaceMatches('/[^a-z0-9]/', ''), 'machupicc'))
        ->sort()->take(5)
        ->map(fn ($p) => asset('img/tours/'.rawurlencode(basename($p))))->values();
    if ($slides->isEmpty() && $featured?->imagen_url) { $slides = collect([$featured->imagen_url]); }
    $why = __('site.why.items');
    $whyIcons = [
        '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5 5-2Z"/>',
        '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6M16 11a3 3 0 1 0 0-6M18 14c2.2.6 3.5 2.4 3.5 5"/>',
        '<path d="M12 3 4 6v6c0 5 3.4 8 8 9 4.6-1 8-4 8-9V6l-8-3Z"/><path d="m8.5 12 2.5 2.5L16 9.5"/>',
        '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3 10h18M7 15h3"/>',
    ];
@endphp

@section('content')
{{-- ============ HERO (primer panel) ============ --}}
<section class="hero2" @if($heroImg) style="--hero-img:url('{{ $heroImg }}')" @endif>
    @if($heroVideo)
        <div class="hero2-media" aria-hidden="true">
            <video src="{{ asset('img/'.rawurlencode($heroVideo)) }}" @if($heroImg) poster="{{ $heroImg }}" @endif autoplay muted loop playsinline preload="metadata"></video>
        </div>
    @endif
    <div class="container hero2-grid">
        <div class="hero2-copy">
            <p class="hero2-kicker">{{ __('site.hero.kicker') }}</p>
            <h1>
                <span>{{ __('site.hero.line1') }}</span>
                <span class="grad">{{ __('site.hero.line2') }}</span>
            </h1>
            <p class="hero2-sub">{{ __('site.hero.sub') }}</p>

            <ul class="hero2-stats">
                <li><strong>{{ $stats['tours'] }}+</strong><span>{{ __('site.hero.stat_tours') }}</span></li>
                @if($stats['rating'])<li><strong>★ {{ number_format($stats['rating'], 1) }}</strong><span>{{ __('site.hero.stat_rating') }}</span></li>@endif
                @if($stats['travelers'] > 0)<li><strong>{{ \App\Support\SiteStats::short($stats['travelers']) }}</strong><span>{{ __('site.hero.stat_travelers') }}</span></li>@endif
            </ul>

            <div class="hero2-cta">
                <a href="{{ route('tours') }}" class="btn btn-grad btn-lg">{{ __('site.hero.cta') }} <span aria-hidden="true">→</span></a>
                <a href="https://wa.me/{{ $whats }}" target="_blank" rel="noopener" class="btn btn-glass btn-lg">{{ __('site.hero.cta2') }}</a>
            </div>
        </div>

        @if($featured)
            <a class="hero2-card" href="{{ route('tour.detalle', $featured->slug) }}">
                <div class="hero2-slides" data-slides>
                    @foreach($slides as $i => $src)<img src="{{ $src }}" alt="" @class(['on' => $i === 0]) onerror="this.remove()">@endforeach
                </div>
                <span class="chip chip-badge">{{ __('site.hero.badge') }}</span>
                <div class="hero2-card-body">
                    <h2>{{ __('site.hero.card_title') }}</h2>
                    <p>{{ __('site.hero.card_text') }}</p>
                    <span class="btn btn-white btn-sm">{{ __('site.hero.card_cta') }}</span>
                </div>
            </a>
        @endif
    </div>
</section>

{{-- ============ Franja de confianza ============ --}}
<section class="trustbar" aria-label="{{ __('site.nav.why') }}">
    <ul class="container trustbar-row">
        <li><svg viewBox="0 0 24 24"><path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ __('site.trust.local') }}</li>
        <li><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>{{ __('site.trust.guides') }}</li>
        <li><svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="3"/><circle cx="5" cy="10" r="2.2"/><circle cx="19" cy="10" r="2.2"/><path d="M6 20c0-3.5 2.7-6 6-6s6 2.5 6 6"/></svg>{{ __('site.trust.small') }}</li>
        <li><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ __('site.trust.support') }}</li>
        <li><svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>{{ __('site.trust.secure') }}</li>
    </ul>
</section>

{{-- ============ Por qué elegirnos ============ --}}
<section class="sec" id="diferenciais">
    <div class="container">
        <header class="sec-head center">
            <p class="eyebrow-c">{{ __('site.why.eyebrow') }}</p>
            <h2>{{ __('site.why.title') }}</h2>
            <p>{{ __('site.why.sub') }}</p>
        </header>
        <div class="why-grid">
            @foreach($why as $i => $item)
                <article class="why-card">
                    <span class="why-ico"><svg viewBox="0 0 24 24">{!! $whyIcons[$i] !!}</svg></span>
                    <h3>{{ $item['t'] }}</h3>
                    <p>{{ $item['d'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ Categorías ============ --}}
@if($categorias->isNotEmpty())
<section class="sec" id="categorias">
    <div class="container">
        <header class="sec-head row-head">
            <div>
                <p class="eyebrow-c">{{ __('extra.cat_e') }}</p>
                <h2>{{ __('extra.cat_t') }}</h2>
                <p>{{ __('extra.cat_s') }}</p>
            </div>
            <div class="head-tools"><a href="{{ route('tours') }}" class="link-arrow">{{ __('extra.cat_all') }} →</a></div>
        </header>
        <div class="cat-grid">
            @foreach($categorias as $c)
                <a class="cat-card" href="{{ route('tours', ['categoria' => $c['nombre']]) }}">
                    @if($c['imagen'])<img src="{{ $c['imagen'] }}" alt="" loading="lazy" decoding="async" onerror="this.remove()">@endif
                    <span class="cat-body">
                        <small>{{ $c['total'] }} {{ __('extra.cat_n') }}</small>
                        <strong>{{ $c['nombre'] }}</strong>
                        <em>{{ __('extra.cat_go') }} →</em>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ Experiencia destacada ============ --}}
@if($destacado)
@php
    $gal = $destacado->galeria_urls;
    $incl = collect(preg_split('/\R/', (string) $destacado->t('incluye')))->map('trim')->filter()->take(3);
@endphp
<section class="sec feat-sec" id="destacado">
    <div class="container feat">
        <div class="feat-media">
            @if(! empty($gal[0]))<img class="feat-a" src="{{ $gal[0] }}" alt="{{ $destacado->t('nombre') }}" loading="lazy" decoding="async">@endif
            @if(! empty($gal[1]))<img class="feat-b" src="{{ $gal[1] }}" alt="" loading="lazy" decoding="async">@endif
            <span class="feat-badge">{{ __('extra.feat_k') }}</span>
        </div>
        <div class="feat-copy">
            <p class="eyebrow-c">{{ __('extra.feat_e') }}</p>
            <h2>{{ $destacado->t('nombre') }}</h2>
            <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $destacado->t('descripcion')), 260) }}</p>
            @if($incl->isNotEmpty())
                <ul class="checklist">@foreach($incl as $i)<li>{{ $i }}</li>@endforeach</ul>
            @endif
            <div class="feat-cta">
                <a class="btn btn-grad btn-lg" href="{{ route('tour.detalle', $destacado->slug) }}">{{ __('extra.feat_book') }} →</a>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ============ Tours populares ============ --}}
<section class="sec sec-alt" id="tours">
    <div class="container">
        <header class="sec-head row-head">
            <div>
                <p class="eyebrow-pill">{{ __('site.popular.eyebrow') }}</p>
                <h2>{{ __('site.popular.title') }}</h2>
                <p>{{ __('site.popular.sub') }}</p>
            </div>
            <div class="head-tools">
                <a href="{{ route('tours') }}" class="link-arrow">{{ __('site.popular.all') }} →</a>
                @if($tours->count() > 3)
                    <button type="button" class="round-btn" data-slide="-1" aria-label="{{ __('site.popular.prev') }}">‹</button>
                    <button type="button" class="round-btn" data-slide="1" aria-label="{{ __('site.popular.next') }}">›</button>
                @endif
            </div>
        </header>

        @if($tours->isNotEmpty())
            <div class="slider" data-slider tabindex="0">
                @foreach($tours as $tour)
                    @include('partials.tour-card', ['tour' => $tour])
                @endforeach
            </div>
        @else
            <p class="empty">{{ __('site.popular.empty') }}</p>
        @endif
    </div>
</section>

{{-- ============ Secciones del inicio (se administran en el panel: Tour místico, Tour clásico, Machu Picchu...) ============ --}}
@foreach($secciones as $seccion)
<section class="sec @if($loop->even) sec-alt @endif" id="seccion-{{ $seccion->slug }}">
    <div class="container">
        <header class="sec-head row-head">
            <div>
                <h2>{{ $seccion->t('nombre') }}</h2>
                @if($seccion->t('descripcion'))<p>{{ $seccion->t('descripcion') }}</p>@endif
            </div>
            <div class="head-tools">
                <a href="{{ route('tours', ['seccion' => $seccion->slug]) }}" class="link-arrow">{{ __('site.popular.all') }} →</a>
                @if($seccion->toursPublicos->count() > 3)
                    <button type="button" class="round-btn" data-slide="-1" aria-label="{{ __('site.popular.prev') }}">‹</button>
                    <button type="button" class="round-btn" data-slide="1" aria-label="{{ __('site.popular.next') }}">›</button>
                @endif
            </div>
        </header>
        <div class="slider" data-slider tabindex="0">
            @foreach($seccion->toursPublicos as $tour)
                @include('partials.tour-card', ['tour' => $tour])
            @endforeach
        </div>
    </div>
</section>
@endforeach

{{-- ============ Destinos ============ --}}
<section class="sec dest-sec" id="destinos">
    <div class="container">
        <header class="sec-head row-head">
            <div>
                <p class="eyebrow-c">{{ __('extra.dest_e') }}</p>
                <h2>{{ __('extra.dest_t') }}</h2>
                <p>{{ __('extra.dest_s') }}</p>
            </div>
            <div class="head-tools"><a href="{{ route('tours') }}" class="link-arrow">{{ __('extra.cat_all') }} →</a></div>
        </header>
        <div class="dest-grid">
            @foreach($destinos as $d)
                <a class="dest-card" href="{{ route('tours', ['destino' => $d['nombre']]) }}">
                    @if($d['imagen'])<img src="{{ $d['imagen'] }}" alt="" loading="lazy" decoding="async" onerror="this.remove()">@endif
                    <span class="dest-n">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="dest-body">
                        <strong>{{ $d['nombre'] }}</strong>
                        <small>{{ $d['total'] > 0 ? $d['total'].' '.__('extra.dest_n') : __('extra.dest_soon') }}</small>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ Reseñas reales (solo si hay datos en config/mayo.php) ============ --}}
@if(count($reviews))
<section class="sec">
    <div class="container">
        <header class="sec-head center">
            <p class="eyebrow-c">{{ __('site.reviews.eyebrow') }}</p>
            <h2>{{ __('site.reviews.title') }}</h2>
        </header>
        <div class="reviews">
            @if($stats['rating'])
                <aside class="rating-box">
                    <strong>{{ __('site.reviews.excellent') }}</strong>
                    <p class="stars" aria-label="{{ number_format($stats['rating'], 1) }} / 5">★★★★★</p>
                    <p class="rating-num">{{ number_format($stats['rating'], 1) }} / 5</p>
                    @if($stats['rating_count'])<small>{{ __('site.reviews.based', ['n' => $stats['rating_count']]) }}</small>@endif
                </aside>
            @endif
            <div class="slider slider-reviews" data-slider tabindex="0">
                @foreach($reviews as $r)
                    <blockquote class="review">
                        <p class="stars" aria-label="{{ $r['stars'] ?? 5 }} / 5">{{ str_repeat('★', (int) ($r['stars'] ?? 5)) }}</p>
                        <p class="review-text">{{ $r['text'] }}</p>
                        <footer>
                            <strong>{{ $r['name'] }}</strong>
                            <small>{{ $r['country'] ?? '' }}@isset($r['date']) · {{ \Illuminate\Support\Carbon::parse($r['date'])->translatedFormat('M Y') }}@endisset</small>
                            @if(!empty($r['url']))<a href="{{ $r['url'] }}" target="_blank" rel="noopener">{{ __('site.reviews.read') }} →</a>@endif
                        </footer>
                    </blockquote>
                @endforeach
            </div>
        </div>
        <div class="op-invite">
            <p>{{ __('resenas.sub') }}</p>
            <div>
                <a class="btn btn-gold" href="{{ route('opiniones') }}#opinar">{{ __('resenas.leave') }}</a>
                <a class="link-arrow" href="{{ route('opiniones') }}">{{ __('resenas.see_all') }} →</a>
            </div>
        </div>
    </div>
</section>
@else
<section class="sec">
    <div class="container">
        <div class="op-invite">
            <p><strong>{{ __('resenas.title') }}</strong> — {{ __('resenas.sub') }}</p>
            <div><a class="btn btn-gold" href="{{ route('opiniones') }}#opinar">{{ __('resenas.leave') }}</a></div>
        </div>
    </div>
</section>
@endif

{{-- ============ Movilidad y servicios ============ --}}
@php
    $wa = 'https://wa.me/'.config('mayo.whatsapp').'?text=';
    $fotoMov = glob(public_path('img/transporte.*')) ? asset('img/'.basename(glob(public_path('img/transporte.*'))[0])) : null;
@endphp
<section class="sec mob-sec" id="movilidad">
    <div class="container mob">
        <div class="mob-media" @if($fotoMov) style="--mob-img:url('{{ $fotoMov }}')" @endif>
            <span class="mob-safe">✓ {{ __('extra.mob_safe') }}</span>
        </div>
        <div class="mob-copy">
            <p class="eyebrow-c">{{ __('extra.mob_e') }}</p>
            <h2>{{ __('extra.mob_t') }}</h2>
            <p>{{ __('extra.mob_s') }}</p>
            <a class="mob-link" href="{{ $wa.rawurlencode(__('extra.wa_fleet')) }}" target="_blank" rel="noopener">
                <span><strong>{{ __('extra.mob_fleet_t') }}</strong><small>{{ __('extra.mob_fleet_d') }}</small></span><b>→</b></a>
            <a class="mob-link" href="{{ $wa.rawurlencode(__('extra.wa_vip')) }}" target="_blank" rel="noopener">
                <span><strong>{{ __('extra.mob_vip_t') }}</strong><small>{{ __('extra.mob_vip_d') }}</small></span><b>→</b></a>
        </div>
    </div>
</section>

{{-- ============ Cifras ============ --}}
<section class="numbers">
    <div class="container">
        <h2>{{ __('site.numbers.title') }}</h2>
        <ul class="numbers-grid">
            @if($stats['years'])<li><strong>{{ $stats['years'] }}+</strong><span>{{ __('site.numbers.years') }}</span></li>@endif
            @if($stats['travelers'] > 0)<li><strong>{{ \App\Support\SiteStats::short($stats['travelers']) }}</strong><span>{{ __('site.numbers.travelers') }}</span></li>@endif
            <li><strong>{{ $stats['tours'] }}</strong><span>{{ __('site.numbers.tours') }}</span></li>
            <li><strong>24/7</strong><span>{{ __('site.numbers.support') }}</span></li>
        </ul>
    </div>
</section>

{{-- ============ FAQ ============ --}}
<section class="sec" id="faq">
    <div class="container">
        <header class="sec-head center">
            <p class="eyebrow-c">{{ __('site.faq.eyebrow') }}</p>
            <h2>{{ __('site.faq.title') }}</h2>
            <p>{{ __('site.faq.sub') }}</p>
        </header>
        <div class="faq-grid">
            @foreach(__('site.faq.items') as $f)
                <details class="faq-item">
                    <summary>{{ $f['q'] }}</summary>
                    <p>{{ $f['a'] }}</p>
                </details>
            @endforeach
        </div>
        <p class="faq-more">{{ __('site.faq.more') }} <a href="{{ route('contacto') }}">{{ __('site.faq.more_cta') }}</a></p>
    </div>
</section>

{{-- ============ CTA final ============ --}}
<section class="cta-band" style="@if($heroImg)--hero-img:url('{{ $heroImg }}')@endif">
    <div class="container">
        <h2>{{ __('site.cta.title') }}</h2>
        <p>{{ __('site.cta.text') }}</p>
        <div class="hero2-cta center">
            <a href="{{ route('contacto') }}" class="btn btn-grad btn-lg">{{ __('site.cta.ask') }} →</a>
            <a href="https://wa.me/{{ $whats }}" target="_blank" rel="noopener" class="btn btn-glass btn-lg">{{ __('site.cta.whatsapp') }}</a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-slides]').forEach(box => {
    const imgs = [...box.querySelectorAll('img')];
    if (imgs.length < 2 || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    imgs[0].classList.add('on');
    let i = 0;
    setInterval(() => { imgs[i].classList.remove('on'); i = (i + 1) % imgs.length; imgs[i].classList.add('on'); }, 4500);
});
</script>
@endpush