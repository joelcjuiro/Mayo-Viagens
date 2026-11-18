@extends('layouts.app')
@section('title', __('blog.meta_title'))
@section('description', __('blog.meta_desc'))
@push('styles')<link rel="stylesheet" href="{{ \App\Support\Media::v('css/blog.css') }}">@endpush

@section('content')
<section class="page-head">
    <div class="container">
        <p class="eyebrow">{{ __('blog.eyebrow') }}</p>
        <h1>{{ __('blog.title') }}</h1>
        <p class="lead-dark">{{ __('blog.sub') }}</p>

        <div class="bl-tools">
            <nav class="bl-chips" aria-label="{{ __('blog.eyebrow') }}">
                <a href="{{ route('blog') }}" @class(['on' => ! $categoria])>{{ __('blog.all') }}</a>
                @foreach(\App\Models\Articulo::CATEGORIAS as $c)
                    <a href="{{ route('blog', ['categoria' => $c]) }}" @class(['on' => $categoria === $c])>{{ __('blog.cats.'.$c) }}</a>
                @endforeach
            </nav>
            <form class="bl-search" method="GET" action="{{ route('blog') }}" role="search">
                @if($categoria)<input type="hidden" name="categoria" value="{{ $categoria }}">@endif
                <input type="search" name="q" value="{{ $q }}" maxlength="60" placeholder="{{ __('blog.search_ph') }}" aria-label="{{ __('blog.search') }}">
                <button type="submit">{{ __('blog.search') }}</button>
            </form>
        </div>
    </div>
</section>

<section class="sec">
    <div class="container">
        @if($destacado)
            @php $f = $destacado->fecha(); @endphp
            <article class="bl-feat">
                <a class="bl-media" href="{{ $destacado->url }}" tabindex="-1" aria-hidden="true">
                    @if($destacado->imagen_url)<img src="{{ $destacado->imagen_url }}" alt="{{ $destacado->imagen_alt }}" fetchpriority="high" decoding="async" width="1000" height="625">@endif
                </a>
                <div class="bl-body">
                    <p class="bl-meta"><span class="bl-cat bl-cat-{{ $destacado->categoria }}">{{ __('blog.featured') }} · {{ __('blog.cats.'.$destacado->categoria) }}</span>
                        <time datetime="{{ $f->toDateString() }}">{{ $f->locale(app()->getLocale())->translatedFormat('j M Y') }}</time> <span>· {{ $destacado->minutos() }} {{ __('blog.min') }}</span></p>
                    <h2><a href="{{ $destacado->url }}">{{ $destacado->t('titulo') }}</a></h2>
                    <p>{{ $destacado->extracto(220) }}</p>
                    <p><a class="btn btn-gold" href="{{ $destacado->url }}">{{ __('blog.read_more') }} →</a></p>
                </div>
            </article>
        @endif

        @if($articulos->count())
            <div class="bl-grid">
                @foreach($articulos as $a)@include('blog._card', ['a' => $a])@endforeach
            </div>
            {{ $articulos->links('partials.pagination', ['lang' => app()->getLocale()]) }}
        @elseif(! $destacado)
            <div class="bl-empty">
                <p>{{ $filtrando ? __('blog.empty_filter') : __('blog.empty') }}</p>
                <p>
                    @if($filtrando)<a class="btn btn-outline" href="{{ route('blog') }}">{{ __('blog.clear') }}</a>
                    @else<a class="btn btn-gold" href="{{ route('tours') }}">{{ __('blog.cta_tours') }}</a>@endif
                </p>
            </div>
        @endif
    </div>
</section>

{{-- Pasajeros: comentarios y fotos reales --}}
@unless($filtrando)
<section class="sec bl-trav" id="viajeros">
    <div class="container">
        <header class="sec-head center">
            <p class="eyebrow-c">{{ __('blog.trav_e') }}</p>
            <h2>{{ __('blog.trav_t') }}</h2>
            <p>{{ __('blog.trav_s') }}</p>
        </header>

        @if($viajeros->isNotEmpty())
            <div class="bl-tgrid">
                @foreach($viajeros as $r)
                    <article class="bl-t">
                        <span class="stars" aria-label="{{ $r->calificacion }} {{ __('resenas.stars_of') }}">{{ str_repeat('★', $r->calificacion) }}</span>
                        @include('partials.fotos', ['galeria' => $r->fotosGaleria(), 'grupo' => 'bl'.$r->id])
                        <p>{{ \Illuminate\Support\Str::limit($r->comentario, 170) }}</p>
                        <footer>
                            <strong>{{ $r->nombre }}</strong>
                            <span>{{ $r->pais }}@if($r->pais) · @endif{{ $r->created_at->locale(app()->getLocale())->translatedFormat('M Y') }}</span>
                            @if($r->tour)<span>{{ __('blog.trav_on') }} <a href="{{ route('tour.detalle', $r->tour->slug) }}" style="color:var(--acc)">{{ $r->tour->nombre }}</a></span>@endif
                        </footer>
                    </article>
                @endforeach
            </div>
        @endif

        <div class="bl-trav-cta">
            <div>
                <h3>{{ __('blog.trav_cta_t') }}</h3>
                <p>{{ __('blog.trav_cta_p') }}</p>
            </div>
            <div>
                <a class="btn btn-gold btn-lg" href="{{ route('opiniones') }}#opinar">{{ __('blog.trav_cta') }}</a>
                <a class="link-arrow" href="{{ route('opiniones') }}">{{ __('blog.trav_all') }} →</a>
            </div>
        </div>
    </div>
</section>
@endunless
@endsection
