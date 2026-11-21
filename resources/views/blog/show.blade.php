@extends('layouts.app')
@php
    $titulo = $articulo->t('titulo');
    $desc = trim((string) $articulo->t('meta_descripcion')) ?: $articulo->extracto(155);
    $f = $articulo->fecha();
    $url = $articulo->url;
    $wa = 'https://wa.me/'.config('mayo.whatsapp').'?text='.rawurlencode(__('blog.wa_text', ['title' => $titulo]));
    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => \Illuminate\Support\Str::limit($titulo, 110, ''),
        'description' => $desc,
        'datePublished' => $f->toIso8601String(),
        'dateModified' => $articulo->updated_at->toIso8601String(),
        'mainEntityOfPage' => $url,
        'author' => ['@type' => 'Organization', 'name' => 'Mayo Viagens'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Mayo Viagens'],
    ];
    if ($articulo->imagen_url) { $ld['image'] = [$articulo->imagen_url]; }
@endphp
@section('title', $titulo.' | Blog Mayo Viagens')
@section('description', $desc)
@if($articulo->imagen_url)@section('og_image', $articulo->imagen_url)@endif
@push('styles')<link rel="stylesheet" href="{{ \App\Support\Media::v('css/blog.css') }}">@endpush
@push('head')<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>@endpush

@section('content')
@if($vistaPrevia)
    <div class="bl-preview" role="status">
        {{ $articulo->situacion === 'publicado' ? __('blog.preview_live') : ($articulo->situacion === 'programado' ? __('blog.preview_scheduled') : __('blog.preview_draft')) }}
        <a href="{{ route('admin.blog.edit', $articulo->id) }}">{{ __('blog.preview_edit') }}</a>
    </div>
@endif

<header class="bl-art-head">
    <div class="bl-narrow">
        <p class="bl-crumbs"><a href="{{ route('inicio') }}">{{ __('blog.home') }}</a> / <a href="{{ route('blog') }}">{{ __('blog.nav') }}</a> / <a href="{{ route('blog', ['categoria' => $articulo->categoria]) }}">{{ __('blog.cats.'.$articulo->categoria) }}</a></p>
        <p class="bl-meta"><span class="bl-cat bl-cat-{{ $articulo->categoria }}">{{ __('blog.cats.'.$articulo->categoria) }}</span>
            <time datetime="{{ $f->toDateString() }}">{{ $f->locale(app()->getLocale())->translatedFormat('j F Y') }}</time>
            <span>· {{ $articulo->minutos() }} {{ __('blog.min') }}</span></p>
        <h1>{{ $titulo }}</h1>
        @if(filled($articulo->t('resumen')))<p class="bl-lead">{{ $articulo->t('resumen') }}</p>@endif
    </div>
    @if($articulo->imagen_url)
        <figure class="bl-cover" style="margin-bottom:0">
            <img src="{{ $articulo->imagen_url }}" alt="{{ $articulo->t('imagen_alt') ?: $titulo }}" width="1000" height="563" fetchpriority="high" decoding="async">
        </figure>
    @endif
</header>

<article class="bl-narrow">
    <div class="bl-prose">{!! $articulo->html() !!}</div>

    <div class="bl-share">
        <strong>{{ __('blog.share') }}:</strong>
        <a href="https://wa.me/?text={{ rawurlencode($titulo.' '.$url) }}" target="_blank" rel="noopener">{{ __('blog.share_wa') }}</a>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}" target="_blank" rel="noopener">{{ __('blog.share_fb') }}</a>
        <button type="button" data-copy="{{ $url }}" data-copied="{{ __('blog.copied') }}">{{ __('blog.copy') }}</button>
        <button type="button" data-share hidden>{{ __('blog.share_native') }}</button>
    </div>

    <aside class="bl-cta">
        <h2>{{ __('blog.cta_t') }}</h2>
        <p>{{ __('blog.cta_p') }}</p>
        <div>
            <a class="btn btn-gold" href="{{ route('tours') }}">{{ __('blog.cta_tours') }}</a>
            <a class="btn btn-glass" href="{{ $wa }}" target="_blank" rel="noopener">{{ __('blog.cta_wa') }}</a>
        </div>
    </aside>

    <aside class="bl-cta" style="background:#e5f4e0;color:var(--ink-900)">
        <h2 style="color:var(--ink-900)">{{ __('blog.trav_cta_t') }}</h2>
        <p style="color:var(--muted)">{{ __('blog.trav_cta_p') }}</p>
        <div><a class="btn btn-gold" href="{{ route('opiniones') }}#opinar">{{ __('blog.trav_cta') }}</a></div>
    </aside>
</article>

@if($relacionados->isNotEmpty())
<section class="sec">
    <div class="container">
        <header class="sec-head"><h2>{{ __('blog.related') }}</h2></header>
        <div class="bl-grid">@foreach($relacionados as $a)@include('blog._card', ['a' => $a])@endforeach</div>
        <p style="margin-top:1.5rem"><a class="link-arrow" href="{{ route('blog') }}">{{ __('blog.back') }}</a></p>
    </div>
</section>
@endif
@endsection
