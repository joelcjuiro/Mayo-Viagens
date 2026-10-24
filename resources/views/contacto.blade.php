@extends('layouts.app')
@section('title', __('contactpage.meta_title'))

@section('content')
@php
    $whats = config('mayo.whatsapp');
    $waLink = 'https://wa.me/'.$whats;
@endphp

<section class="page-head">
    <div class="container">
        <p class="eyebrow">{{ __('contactpage.eyebrow') }}</p>
        <h1>{{ __('contactpage.title') }}</h1>
        <p class="lead-dark">{{ __('contactpage.sub') }}</p>
    </div>
</section>

<section class="form-section">
    <div class="container">

        <div class="contact-cards">
            <a class="cc" href="{{ $waLink }}" target="_blank" rel="noopener">
                <span class="cc-ico"><svg viewBox="0 0 24 24"><path d="M4 5h16v11H8l-4 4V5Z"/></svg></span>
                <b>{{ __('contactpage.wa_t') }}</b>
                <span>+{{ $whats }}</span>
                <em>{{ __('contactpage.wa_cta') }} →</em>
            </a>
            <div class="cc">
                <span class="cc-ico"><svg viewBox="0 0 24 24"><path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                <b>{{ __('contactpage.loc_t') }}</b>
                <span>{{ __('contactpage.loc_d') }}</span>
            </div>
            <div class="cc">
                <span class="cc-ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                <b>{{ __('contactpage.hours_t') }}</b>
                <span>{{ __('contactpage.hours_d') }}</span>
            </div>
            <a class="cc" href="{{ route('reservas') }}">
                <span class="cc-ico"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg></span>
                <b>{{ __('contactpage.book_t') }}</b>
                <span>{{ __('contactpage.book_d') }}</span>
                <em>{{ __('contactpage.book_cta') }} →</em>
            </a>
        </div>

        <div class="contact-grid">
            <form method="POST" action="{{ route('contacto.store') }}" class="card-form">
                @csrf
                <h2>{{ __('contactpage.form_t') }}</h2>
                <p class="lead-dark" style="margin:0">{{ __('contactpage.form_s') }}</p>

                @if(session('success'))<div class="alert alert-ok" role="status">{{ session('success') }}</div>@endif

                <div class="row">
                    <div class="field"><label for="nombre">{{ __('contactpage.name') }}</label>
                        <input id="nombre" name="nombre" value="{{ old('nombre') }}" required autocomplete="name">
                        @error('nombre')<small class="error">{{ $message }}</small>@enderror</div>
                    <div class="field"><label for="email">{{ __('contactpage.email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                        @error('email')<small class="error">{{ $message }}</small>@enderror</div>
                </div>
                <div class="row">
                    <div class="field"><label for="telefono">{{ __('contactpage.phone') }}</label>
                        <input id="telefono" name="telefono" value="{{ old('telefono') }}" autocomplete="tel">
                        @error('telefono')<small class="error">{{ $message }}</small>@enderror</div>
                    <div class="field"><label for="asunto">{{ __('contactpage.subject') }}</label>
                        <input id="asunto" name="asunto" value="{{ old('asunto') }}" required>
                        @error('asunto')<small class="error">{{ $message }}</small>@enderror</div>
                </div>
                <div class="field"><label for="mensaje">{{ __('contactpage.message') }}</label>
                    <textarea id="mensaje" name="mensaje" rows="5" required>{{ old('mensaje') }}</textarea>
                    @error('mensaje')<small class="error">{{ $message }}</small>@enderror</div>

                <div class="field field-check">
                    <label class="check-line">
                        <input type="checkbox" name="acepta_terminos" value="1" @checked(old('acepta_terminos')) required>
                        <span>{!! __('contactpage.accept', ['link' => '<a href="'.e(route('terminos')).'" target="_blank" rel="noopener">'.e(__('contactpage.terms_link')).'</a>']) !!}</span>
                    </label>
                    @error('acepta_terminos')<small class="error">{{ $message }}</small>@enderror
                </div>

                <button class="btn btn-gold btn-lg" type="submit">{{ __('contactpage.send') }}</button>
            </form>

            <aside class="contact-info">
                <h2>{{ __('contactpage.side_t') }}</h2>
                <p><strong>{{ __('contactpage.wa_t') }}</strong><br><a href="{{ $waLink }}" target="_blank" rel="noopener">+{{ $whats }}</a></p>
                <p><strong>{{ __('contactpage.loc_t') }}</strong><br>{{ __('contactpage.loc_d') }}</p>
                <p><strong>{{ __('contactpage.support_t') }}</strong><br>{{ __('contactpage.support_d') }}</p>
                <a class="btn btn-gold" href="{{ $waLink }}" target="_blank" rel="noopener">{{ __('contactpage.side_btn') }}</a>

                <h3 style="margin-top:1rem">{{ __('contactpage.faq_t') }}</h3>
                <div class="mini-faq">
                    @foreach(__('contactpage.faq') as $f)
                        <details>
                            <summary>{{ $f['q'] }}</summary>
                            <p>{{ $f['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </aside>
        </div>

        <div class="map-wrap">
            <iframe title="{{ __('contactpage.map_t') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps?q=Cusco%2C%20Peru&output=embed"></iframe>
        </div>
    </div>
</section>
@endsection