@extends('layouts.app')
@section('title', __('site.meta.tours_title'))

@section('content')
<section class="page-head">
    <div class="container">
        <p class="eyebrow">{{ __('site.tours_page.eyebrow') }}</p>
        <h1>{{ __('site.tours_page.title') }}</h1>
        <p class="lead-dark">{{ __('site.tours_page.sub') }}</p>

        <form class="page-search" action="{{ route('tours') }}" method="GET" role="search">
            <input type="search" name="q" value="{{ $q ?? '' }}"
                   placeholder="{{ __('site.tours_page.search_placeholder') }}"
                   aria-label="{{ __('site.tours_page.search_placeholder') }}">
            <button class="btn btn-gold" type="submit">{{ __('site.tours_page.search_button') }}</button>
        </form>

        @if(!empty($q))
            <p class="search-note">{{ __('site.tours_page.results_for') }} “{{ $q }}” · <a href="{{ route('tours') }}">{{ __('site.tours_page.clear') }}</a></p>
        @endif

        @if(!empty($destino))
            <p class="search-note">{{ __('site.tours_page.filtered_by') }} “{{ $destino }}” · <a href="{{ route('tours') }}">{{ __('site.tours_page.clear') }}</a></p>
        @endif
    </div>
</section>

<section class="tours-section">
    <div class="container">
        @if($tours->count())
            <div class="tour-grid rich">
                @foreach($tours as $tour)
                    @include('partials.tour-card', ['tour' => $tour])
                @endforeach
            </div>
            {{ $tours->links('partials.pagination') }}
        @else
            <p class="empty">{{ !empty($q) ? __('site.tours_page.none_for') : __('site.tours_page.empty') }}</p>
        @endif
    </div>
</section>
@endsection