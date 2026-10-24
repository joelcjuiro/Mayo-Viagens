@extends('layouts.app')
@section('title', __('site.meta.about_title'))

@push('styles')
<script>document.documentElement.classList.add('ab-js')</script>
<link rel="stylesheet" href="{{ asset('css/nosotros.css') }}">
@endpush

@php
    $stats = \App\Support\SiteStats::get();
    $whats = config('mayo.whatsapp');
    $photo = collect(['machupicchu.jpg', 'machupicchu (2).jpg'])->first(fn ($f) => file_exists(public_path('img/tours/'.$f)));
    $icons = [
        '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r=".8"/>',
        '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        '<path d="M12 3 4 6v6c0 5 3.4 8 8 9 4.6-1 8-4 8-9V6l-8-3Z"/><path d="m8.5 12 2.5 2.5L16 9.5"/>',
    ];
    $photoUrl = $photo ? asset('img/tours/'.rawurlencode($photo)) : null;
@endphp

@section('content')
<section class="ab-hero">
    <div class="container ab-hero-grid">
        <div>
            <p class="ab-eyebrow">{{ __('nosotros.eyebrow') }}</p>
            <h1>{{ __('nosotros.title') }}</h1>
            <p class="ab-lead">{{ __('nosotros.lead') }}</p>
            <p class="ab-sub">{{ __('nosotros.p2') }}</p>
            <div class="ab-cta">
                <a href="{{ route('tours') }}" class="btn btn-grad btn-lg">{{ __('nosotros.btn_tours') }} <span aria-hidden="true">→</span></a>
                <a href="https://wa.me/{{ $whats }}?text={{ urlencode(__('nosotros.wa_text')) }}" target="_blank" rel="noopener" class="btn btn-glass btn-lg">{{ __('nosotros.btn_wa') }}</a>
            </div>
        </div>
        <figure class="ab-photo" @style(['--ph: url(' . $photoUrl . ')' => $photoUrl])></figure>
    </div>
</section>

<section class="ab-sec">
    <div class="container ab-pillars">
        @foreach(__('nosotros.pillars') as $i => $p)
            <article class="ab-pillar ab-rv" @style(['--d: ' . $i])>
                <span class="ab-ico"><svg viewBox="0 0 24 24">{!! $icons[$i] !!}</svg></span>
                <h2>{{ $p['t'] }}</h2>
                <p>{{ $p['d'] }}</p>
            </article>
        @endforeach
    </div>
</section>

<section class="ab-sec ab-alt">
    <div class="container">
        <header class="ab-head ab-rv"><h2>{{ __('nosotros.values_t') }}</h2><p>{{ __('nosotros.values_s') }}</p></header>
        <div class="ab-values">
            @foreach(__('nosotros.values') as $i => $v)
                <article class="ab-value ab-rv" @style(['--d: ' . $i])><b>0{{ $i + 1 }}</b><h3>{{ $v['t'] }}</h3><p>{{ $v['d'] }}</p></article>
            @endforeach
        </div>
    </div>
</section>

<section class="ab-sec">
    <div class="container">
        <header class="ab-head ab-rv"><h2>{{ __('nosotros.how_t') }}</h2><p>{{ __('nosotros.how_s') }}</p></header>
        <ol class="ab-steps">
            @foreach(__('nosotros.steps') as $i => $s)
                <li class="ab-rv" @style(['--d: ' . $i])><span>{{ $i + 1 }}</span><h3>{{ $s['t'] }}</h3><p>{{ $s['d'] }}</p></li>
            @endforeach
        </ol>
    </div>
</section>

<section class="ab-proof">
    <div class="container">
        <h2>{{ __('nosotros.proof_t') }}</h2>
        <ul class="ab-checks">
            @foreach(__('nosotros.proof') as $c)<li>{{ $c }}</li>@endforeach
        </ul>
        <ul class="ab-stats">
            @if($stats['years'])<li><strong>{{ $stats['years'] }}+</strong><span>{{ __('nosotros.stat_years') }}</span></li>@endif
            @if($stats['travelers'] > 0)<li><strong>{{ \App\Support\SiteStats::short($stats['travelers']) }}</strong><span>{{ __('nosotros.stat_travelers') }}</span></li>@endif
            <li><strong>24/7</strong><span>{{ __('nosotros.stat_support') }}</span></li>
        </ul>
    </div>
</section>

<section class="ab-final">
    <div class="container ab-rv">
        <h2>{{ __('nosotros.cta_t') }}</h2>
        <p>{{ __('nosotros.cta_p') }}</p>
        <div class="ab-cta center">
            <a href="{{ route('contacto') }}" class="btn btn-grad btn-lg">{{ __('site.cta.ask') }} →</a>
            <a href="https://wa.me/{{ $whats }}?text={{ urlencode(__('nosotros.wa_text')) }}" target="_blank" rel="noopener" class="btn btn-glass btn-lg">{{ __('nosotros.btn_wa') }}</a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const els = document.querySelectorAll('.ab-rv');
    if (!('IntersectionObserver' in window)) { els.forEach(e => e.classList.add('in')); return; }
    const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), { threshold: .15 });
    els.forEach(e => io.observe(e));
})();
</script>
@endpush