@extends('layouts.app')
@section('title', __('resenas.meta'))

@section('content')
@php
    $total = array_sum($distribucion);
    $avg = $resumen['avg'];
@endphp
<section class="page-head">
    <div class="container">
        <p class="eyebrow">{{ __('resenas.eyebrow') }}</p>
        <h1>{{ __('resenas.title') }}</h1>
        <p class="lead-dark">{{ __('resenas.sub') }}</p>
    </div>
</section>

<section class="form-section">
    <div class="container">
        @if(session('enviada'))
            <div class="reclamo-ok" role="status">
                <span class="reclamo-ok-ico">✓</span>
                <div><h2>{{ __('resenas.thanks_t') }}</h2><p>{{ __('resenas.thanks_p') }}</p></div>
            </div>
        @endif

        <div class="op-grid">
            <div>
                @if($total > 0)
                    <div class="op-summary">
                        <div class="op-avg"><strong>{{ number_format($avg, 1) }}</strong><span class="stars">{{ str_repeat('★', (int) round($avg)) }}</span><small>{{ __('resenas.based', ['n' => $total]) }}</small></div>
                        <ul class="op-bars">
                            @foreach($distribucion as $s => $n)
                                <li><span>{{ $s }} ★</span><i><b style="width: {{ $total ? round($n / $total * 100) : 0 }}%"></b></i><em>{{ $n }}</em></li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="op-list">
                        @foreach($resenas as $r)
                            <article class="op-card">
                                <header>
                                    <span class="op-av">{{ mb_strtoupper(mb_substr($r->nombre, 0, 1)) }}</span>
                                    <div><strong>{{ $r->nombre }}</strong><small>{{ $r->pais }}@if($r->pais) · @endif{{ $r->created_at->locale(app()->getLocale())->translatedFormat('M Y') }}</small></div>
                                    <span class="stars" aria-label="{{ $r->calificacion }} {{ __('resenas.stars_of') }}">{{ str_repeat('★', $r->calificacion) }}</span>
                                </header>
                                @if($r->tour)<p class="op-tour">{{ __('resenas.on_tour') }} <a href="{{ route('tour.detalle', $r->tour->slug) }}">{{ $r->tour->nombre }}</a></p>@endif
                                <p>{{ $r->comentario }}</p>
                                @if($r->respuesta)<blockquote class="op-reply"><strong>{{ __('resenas.reply') }}</strong><br>{{ $r->respuesta }}</blockquote>@endif
                            </article>
                        @endforeach
                    </div>
                    {{ $resenas->links('partials.pagination', ['lang' => app()->getLocale()]) }}
                @else
                    <p class="empty">{{ __('resenas.none') }}</p>
                @endif
            </div>

            <form method="POST" action="{{ route('opiniones.store') }}" class="card-form op-form" id="opinar" novalidate>
                @csrf
                <input type="text" name="web" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
                <h2>{{ __('resenas.form_t') }}</h2>
                <p class="lead-dark" style="margin:0">{{ __('resenas.form_s') }}</p>
                @if($errors->any())<div class="alert alert-error" role="alert">{{ $errors->first() }}</div>@endif

                <fieldset class="star-input">
                    <legend>{{ __('resenas.rating') }} *</legend>
                    @for($i = 5; $i >= 1; $i--)
                        <input type="radio" id="st{{ $i }}" name="calificacion" value="{{ $i }}" @checked((int) old('calificacion') === $i) required>
                        <label for="st{{ $i }}" title="{{ $i }}">★<span class="sr-only">{{ $i }}</span></label>
                    @endfor
                </fieldset>

                <div class="row">
                    <div class="field"><label for="nombre">{{ __('resenas.name') }} *</label>
                        <input id="nombre" name="nombre" value="{{ old('nombre') }}" required autocomplete="name">@error('nombre')<small class="error">{{ $message }}</small>@enderror</div>
                    <div class="field"><label for="pais">{{ __('resenas.country') }}</label>
                        <input id="pais" name="pais" value="{{ old('pais') }}"></div>
                </div>
                <div class="field"><label for="email">{{ __('resenas.email') }} *</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">@error('email')<small class="error">{{ $message }}</small>@enderror</div>
                <div class="field"><label for="tour_id">{{ __('resenas.tour') }}</label>
                    <select id="tour_id" name="tour_id">
                        <option value="">{{ __('resenas.tour_any') }}</option>
                        @foreach($tours as $t)<option value="{{ $t->id }}" @selected((int) $preseleccion === $t->id)>{{ $t->nombre }}</option>@endforeach
                    </select></div>
                <div class="field"><label for="comentario">{{ __('resenas.comment') }} *</label>
                    <textarea id="comentario" name="comentario" rows="5" required minlength="15" placeholder="{{ __('resenas.comment_ph') }}">{{ old('comentario') }}</textarea>
                    @error('comentario')<small class="error">{{ $message }}</small>@enderror</div>
                <div class="field field-check">
                    <label class="check-line"><input type="checkbox" name="acepta" value="1" required @checked(old('acepta'))>
                        <span>{!! __('resenas.accept', ['link' => '<a href="'.e(route('privacidad')).'" target="_blank" rel="noopener">'.e(__('resenas.privacy')).'</a>']) !!}</span></label>
                    @error('acepta')<small class="error">{{ $message }}</small>@enderror</div>
                <button class="btn btn-gold btn-lg" type="submit">{{ __('resenas.send') }}</button>
            </form>
        </div>
    </div>
</section>
@endsection
