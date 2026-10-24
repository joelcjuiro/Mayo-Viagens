@extends('layouts.app')
@section('title', __('site.meta.book_title'))

@section('content')
<section class="page-head">
    <div class="container">
        <p class="eyebrow">{{ __('site.book_page.eyebrow') }}</p>
        <h1>{{ __('site.book_page.title') }}</h1>
        <p class="lead-dark">{{ __('site.book_page.sub') }}</p>
    </div>
</section>

<section class="form-section">
    <div class="container narrow">
        @if(session('success'))<div class="alert alert-ok" role="status">{{ session('success') }}</div>@endif

        <form method="POST" action="{{ route('reservas.store') }}" class="card-form" novalidate>
            @csrf
            <div class="field">
                <label for="tour_id">{{ __('site.book_page.tour') }}</label>
                <select id="tour_id" name="tour_id">
                    <option value="">{{ __('site.book_page.undecided') }}</option>
                    @foreach($tours as $t)
                        <option value="{{ $t->id }}" @selected(old('tour_id', request('tour')) == $t->id)>{{ $t->t('nombre') }}</option>
                    @endforeach
                </select>
                @error('tour_id')<small class="error">{{ $message }}</small>@enderror
            </div>

            <div class="row">
                <div class="field"><label for="nombre">{{ __('site.book_page.name') }}</label>
                    <input id="nombre" name="nombre" value="{{ old('nombre') }}" required autocomplete="name">
                    @error('nombre')<small class="error">{{ $message }}</small>@enderror</div>
                <div class="field"><label for="email">{{ __('site.book_page.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                    @error('email')<small class="error">{{ $message }}</small>@enderror</div>
            </div>

            <div class="row">
                <div class="field"><label for="telefono">{{ __('site.book_page.phone') }}</label>
                    <input id="telefono" name="telefono" value="{{ old('telefono') }}" required autocomplete="tel">
                    @error('telefono')<small class="error">{{ $message }}</small>@enderror</div>
                <div class="field"><label for="fecha">{{ __('site.book_page.date') }}</label>
                    <input id="fecha" type="date" name="fecha" value="{{ old('fecha') }}" min="{{ now()->toDateString() }}" required>
                    @error('fecha')<small class="error">{{ $message }}</small>@enderror</div>
                <div class="field"><label for="personas">{{ __('site.book_page.people') }}</label>
                    <input id="personas" type="number" name="personas" min="1" max="50" value="{{ old('personas', 2) }}" required>
                    @error('personas')<small class="error">{{ $message }}</small>@enderror</div>
            </div>

            <div class="field">
                <label for="mensaje">{{ __('site.book_page.notes') }}</label>
                <textarea id="mensaje" name="mensaje" rows="4">{{ old('mensaje') }}</textarea>
                @error('mensaje')<small class="error">{{ $message }}</small>@enderror
            </div>

            <p class="form-note">🔒 {{ __('site.book_page.note') }}</p>
                        <div class="field field-check">
                <label class="check-line">
                    <input type="checkbox" name="acepta_terminos" value="1" @checked(old('acepta_terminos')) required>
                    <span>{!! __('contactpage.accept', ['link' => '<a href="'.e(route('terminos')).'" target="_blank" rel="noopener">'.e(__('contactpage.terms_link')).'</a>']) !!}</span>
                </label>
                @error('acepta_terminos')<small class="error">{{ $message }}</small>@enderror
            </div>
            <button class="btn btn-gold btn-lg" type="submit">{{ __('site.book_page.send') }}</button>
        </form>
    </div>
</section>
@endsection
