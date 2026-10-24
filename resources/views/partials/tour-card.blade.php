@php
    $icons = [
        'landmark' => '<path d="M3 21h18M5 21V10m14 11V10M9 21V10m6 11V10M2 10l10-6 10 6H2Z"/>',
        'mountain' => '<path d="m3 20 6-11 4 6 3-4 5 9H3Z"/>',
        'water'    => '<path d="M3 9c2 0 2-1.5 4.5-1.5S10 9 12 9s2.5-1.5 4.5-1.5S19 9 21 9M3 14c2 0 2-1.5 4.5-1.5S10 14 12 14s2.5-1.5 4.5-1.5S19 14 21 14M3 19c2 0 2-1.5 4.5-1.5S10 19 12 19s2.5-1.5 4.5-1.5S19 19 21 19"/>',
    ];
    $icon = $icons[$tour->icono] ?? $icons['mountain'];
    $whats = config('mayo.whatsapp');
    $nombre = $tour->t('nombre');
    $descuento = (int) $tour->descuento;
    $nivel = $tour->nivel ? __('site.levels.'.$tour->nivel) : null;
    if ($nivel && str_starts_with($nivel, 'site.')) { $nivel = $tour->nivel; }
    $badge = $tour->badge ? __('site.badges.'.$tour->badge) : null;
    if ($badge && str_starts_with($badge, 'site.')) { $badge = $tour->badge; }
@endphp
<article class="tour-card">
    <a href="{{ route('tour.detalle', $tour->slug) }}" class="tour-media" tabindex="-1" aria-hidden="true">
        @if($tour->imagen_url)
            <img src="{{ $tour->imagen_url }}" alt="" loading="lazy" decoding="async" onerror="this.remove()">
        @endif
        @if($badge)<span class="chip chip-badge">{{ $badge }}</span>@endif
        @if($descuento > 0)<span class="chip chip-off">-{{ $descuento }}%</span>@endif
        <span class="tour-icon"><svg viewBox="0 0 24 24">{!! $icon !!}</svg></span>
    </a>
    <div class="tour-body">
        @if($tour->categoria)<p class="tour-cat">{{ $tour->categoria }}</p>@endif
        <h3><a href="{{ route('tour.detalle', $tour->slug) }}">{{ $nombre }}</a></h3>
        <p class="tour-sum">{{ $tour->resumen }}</p>
        <ul class="tour-facts">
            @if($tour->duracion)<li title="{{ __('site.card.duration') }}">⏱ {{ $tour->t('duracion') }}</li>@endif
            @if($tour->tamano_grupo)<li title="{{ __('site.card.group') }}">👥 {{ $tour->tamano_grupo }} {{ __('site.card.people') }}</li>@endif
            @if($nivel)<li title="{{ __('site.card.level') }}">⛰ {{ $nivel }}</li>@endif
        </ul>
        <div class="tour-foot">
            @if($tour->precio > 0)
                <p class="tour-price">
                    <small>{{ __('site.card.from') }}</small>
                    @if($descuento > 0)<s>US$ {{ number_format($tour->precio, 0) }}</s>@endif
                    <strong>US$ {{ number_format($tour->precio_final, 0) }}</strong>
                    <small>{{ __('site.card.per_person') }}</small>
                </p>
            @endif
            <div class="tour-actions">
                <a class="btn btn-outline btn-sm" href="https://wa.me/{{ $whats }}?text={{ urlencode(__('site.detail.wa_text').$nombre) }}" target="_blank" rel="noopener">{{ __('site.card.ask') }}</a>
                <a class="btn btn-teal btn-sm" href="{{ route('tour.detalle', $tour->slug) }}">{{ __('site.card.details') }} <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </div>
</article>
