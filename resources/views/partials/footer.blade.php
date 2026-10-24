@php
    $whats = config('mayo.whatsapp');
    $social = array_filter(config('mayo.social'));
    $destinos = rescue(fn () => \App\Models\Tour::publicos()->select(['id','nombre','slug','destino','imagen','traducciones','created_at'])->latest()->take(5)->get(), collect(), false);
@endphp
<footer class="footer2" id="contato">
    <div class="container f2-grid">

        {{-- Marca --}}
        <div class="f2-brand">
            <img class="f2-logo" src="{{ asset('img/logo.jpeg') }}" alt="Mayo Viagens Cusco Peru">
            <p>{{ __('site.footer.about') }}</p>
            <ul class="f2-checks">
                @foreach(__('site.footer.checks') as $c)<li>{{ $c }}</li>@endforeach
            </ul>
            @php $sello = \App\Support\Legal::esnnaImage(); @endphp
            @if($sello)<a class="f2-seal" href="{{ route('esnna') }}" aria-label="{{ __('contactpage.esnna_link') }}"><img src="{{ $sello }}" alt="{{ __('contactpage.esnna_link') }}" loading="lazy"></a>@endif
            <div class="f2-social">
                @isset($social['facebook'])<a href="{{ $social['facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.3v7A10 10 0 0 0 22 12z"/></svg></a>@endisset
                @isset($social['instagram'])<a href="{{ $social['instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a>@endisset
                @isset($social['youtube'])<a href="{{ $social['youtube'] }}" target="_blank" rel="noopener" aria-label="YouTube"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 7.2a3 3 0 0 0-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 0 0 1 7.2C.5 9 .5 12 .5 12s0 3 .5 4.8a3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1c.5-1.8.5-4.8.5-4.8s0-3-.5-4.8zM9.8 15.3V8.7l5.7 3.3-5.7 3.3z"/></svg></a>@endisset
            </div>
        </div>

        {{-- Links --}}
        <nav class="f2-links" aria-label="Rodapé">
            <div>
                <h2>{{ __('site.footer.destinations') }}</h2>
                <ul>
                    @forelse($destinos as $d)
                        <li><a href="{{ route('tour.detalle', $d->slug) }}">{{ $d->t('nombre') }}</a></li>
                    @empty
                        <li><a href="{{ route('tours') }}">Cusco</a></li>
                    @endforelse
                </ul>
            </div>
            <div>
                <h2>{{ __('site.footer.before') }}</h2>
                <ul>
                    <li><a href="{{ route('tours') }}">{{ __('site.footer.tours') }}</a></li>
                    <li><a href="{{ route('reservas') }}">{{ __('site.footer.book') }}</a></li>
                    <li><a href="https://wa.me/{{ $whats }}" target="_blank" rel="noopener">WhatsApp</a></li>
                    <li><a href="{{ route('contacto') }}">{{ __('site.footer.talk') }}</a></li>
                </ul>
            </div>
            <div>
                <h2>{{ __('site.footer.company') }}</h2>
                <ul>
                    <li><a href="{{ route('nosotros') }}">{{ __('site.footer.about_us') }}</a></li>
                    <li><a href="{{ route('inicio') }}#diferenciais">{{ __('site.footer.why') }}</a></li>
                    <li><a href="{{ route('contacto') }}">{{ __('site.footer.contact') }}</a></li>
                    @if(Route::has('terminos'))<li><a href="{{ route('terminos') }}">{{ __('contactpage.terms_link') }}</a></li>@endif
                    @if(Route::has('privacidad'))<li><a href="{{ route('privacidad') }}">{{ __('contactpage.privacy_link') }}</a></li>@endif
                    @if(Route::has('opiniones'))<li><a href="{{ route('opiniones') }}">{{ __('contactpage.reviews_link') }}</a></li>@endif
                    @if(Route::has('reclamaciones'))<li><a href="{{ route('reclamaciones') }}">Libro de Reclamaciones</a></li>@endif
                    @if(Route::has('esnna'))<li><a href="{{ route('esnna') }}">{{ __('contactpage.esnna_link') }}</a></li>@endif
                </ul>
            </div>
        </nav>

        {{-- Contacto --}}
        <aside class="f2-contact">
            <h2>{{ __('site.footer.contact') }}</h2>
            <a class="f2-item" href="tel:+{{ $whats }}">
                <span class="f2-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg></span>
                <span>{{ config('mayo.whatsapp_display') }}</span>
            </a>
            <div class="f2-item">
                <span class="f2-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                <span>{{ config('mayo.address') }}</span>
            </div>
            <div class="f2-item">
                <span class="f2-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                <span>{{ __('site.footer.support') }}</span>
            </div>
        </aside>
    </div>

    <div class="container">
        <div class="f2-pay">
            <p>{{ __('site.footer.secure') }}</p>
            <ul>
                <li>Visa</li><li>Mastercard</li><li>Amex</li><li>PayPal</li>
            </ul>
        </div>
        <p class="f2-copy">© {{ date('Y') }} Mayo Viagens. {{ __('site.footer.rights') }}</p>
    </div>

    <div class="textile" aria-hidden="true"></div>
</footer>