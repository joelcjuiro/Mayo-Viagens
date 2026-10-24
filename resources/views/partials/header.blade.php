@php
    $whats = config('mayo.whatsapp');
    $todos = rescue(fn () => \App\Models\Tour::publicos()->select(['id','nombre','slug','destino','imagen','created_at'])->latest()->get(), collect(), false);
    // --- Destinos: los habituales siempre aparecen; si cargas un tour con otro destino, se suma solo ---
    $destinoBase = ['Cusco', 'Puno', 'Arequipa', 'Lima'];
    $destGrupos = collect($destinoBase)->mapWithKeys(fn ($d) => [mb_strtolower($d) => ['nombre' => $d, 'items' => collect()]]);
    foreach ($todos->whereNotNull('destino') as $t) {
        $k = mb_strtolower(trim($t->destino));
        if ($k === '') { continue; }
        if (! $destGrupos->has($k)) { $destGrupos->put($k, ['nombre' => trim($t->destino), 'items' => collect()]); }
        $g = $destGrupos->get($k); $g['items']->push($t); $destGrupos->put($k, $g);
    }
    $groupsDestinos = $destGrupos->sortByDesc(fn ($g) => $g['items']->count())->values()
        ->map(fn ($g) => $g + ['url' => route('tours', ['destino' => $g['nombre']])])->all();

    // --- Tours: "Todos los tours" + cada sección del inicio (Tour místico, Machu Picchu...) ---
    $seccionesMenu = rescue(fn () => \App\Models\Seccion::where('activo', true)->orderBy('orden')
        ->with(['toursPublicos' => fn ($q) => $q->latest()])->get()->filter(fn ($s) => $s->toursPublicos->isNotEmpty()), collect(), false);
    $groupsTours = array_merge(
        [['nombre' => __('header.all_tours'), 'url' => route('tours'), 'items' => $todos]],
        $seccionesMenu->map(fn ($s) => ['nombre' => $s->t('nombre'), 'url' => route('tours', ['seccion' => $s->slug]), 'items' => $s->toursPublicos])->values()->all()
    );
    $bg = optional($todos->first(fn ($t) => $t->imagen_url))->imagen_url;

    // Busca el logo en public/img (logo.png, logo.svg, logo.webp...). Si no hay, muestra el nombre en texto.
    $logoFile = collect(glob(public_path('img/logo*.{svg,png,webp,jpg,jpeg}'), GLOB_BRACE) ?: [])
        ->reject(fn ($p) => str_contains(strtolower(basename($p)), 'footer'))
        ->first();
@endphp
<header class="site-header" data-bar>
    <div class="pillbar">
        <a href="{{ route('inicio') }}" class="brand" aria-label="Mayo Viagens">
            @if($logoFile)
                <img src="{{ asset('img/'.rawurlencode(basename($logoFile))) }}" alt="Mayo Viagens">
            @else
                <span class="brand-text">Mayo <b>Viagens</b></span>
            @endif
        </a>

        <button class="nav-toggle" type="button" aria-label="{{ __('header.open_menu') }}" aria-expanded="false" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>

        <div class="nav-wrap" data-nav>
            <ul class="menu">
                <li><a class="nav-btn {{ request()->routeIs('inicio') ? 'active' : '' }}" href="{{ route('inicio') }}">{{ __('header.home') }}</a></li>

                @include('partials.mega', ['id' => 'mega-destinos', 'label' => __('header.destinations'), 'em' => '', 'kicker' => __('header.premium'), 'title' => __('header.dest_title'), 'groups' => $groupsDestinos, 'bg' => $bg])

                @include('partials.mega', ['id' => 'mega-tours', 'label' => __('header.tours'), 'em' => '', 'kicker' => __('header.premium'), 'title' => __('header.tours_title'), 'groups' => $groupsTours, 'limit' => 6, 'bg' => $bg])

                <li class="mega-item" data-mega>
                    <button type="button" class="nav-btn {{ request()->routeIs('nosotros') ? 'active' : '' }}" data-mega-btn aria-expanded="false" aria-controls="mega-nosotros">
                        {{ __('header.about') }} <em>{{ __('header.about_em') }}</em> <span class="chev" aria-hidden="true"></span>
                    </button>
                    <div class="mega mega-about" id="mega-nosotros">
                        <div class="mega-left">
                            <p class="kicker">{{ __('header.about_kicker') }}</p>
                            <h3>Mayo Viagens</h3>
                            <p class="desc">{{ __('header.about_desc') }}</p>
                        </div>
                        <div class="mega-links">
                            <p class="kicker-dim">{{ __('header.explore') }}</p>
                            <a href="{{ route('nosotros') }}">{{ __('header.our_story') }}</a>
                            <a href="{{ route('tours') }}">{{ __('header.all_tours') }}</a>
                            <a href="{{ route('reservas') }}">{{ __('header.reserve') }}</a>
                            <a href="{{ route('contacto') }}">{{ __('header.contact') }}</a>
                        </div>
                        <div class="mega-contact">
                            <div><b>{{ __('header.whatsapp') }}</b><a href="https://wa.me/{{ $whats }}" target="_blank" rel="noopener">+{{ $whats }}</a></div>
                            <div><b>{{ __('header.location') }}</b><span>{{ __('header.location_v') }}</span></div>
                            <div><b>{{ __('header.support') }}</b><span>{{ __('header.support_v') }}</span></div>
                        </div>
                    </div>
                </li>

                <li><a class="nav-btn {{ request()->routeIs('contacto') ? 'active' : '' }}" href="{{ route('contacto') }}">{{ __('header.contact') }}</a></li>
            </ul>

            <div class="bar-actions">
                @php
                    $idiomas = ['pt' => 'Português', 'es' => 'Español', 'en' => 'English'];
                    $actual = app()->getLocale();
                @endphp
                <details class="lang-menu" data-lang>
                    <summary aria-label="Idioma / Language / Idioma">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3Z"/></svg>
                        {{ strtoupper($actual) }}
                        <span class="chev" aria-hidden="true"></span>
                    </summary>
                    <ul>
                        @foreach($idiomas as $code => $nombreIdioma)
                            <li><a href="{{ route('idioma', $code) }}" hreflang="{{ $code }}" @class(['on' => $code === $actual]) @if($code === $actual) aria-current="true" @endif>{{ $nombreIdioma }} <small>{{ strtoupper($code) }}</small></a></li>
                        @endforeach
                    </ul>
                </details>
                <a class="btn btn-gold btn-sm" href="{{ route('reservas') }}">{{ __('header.book') }}</a>
            </div>
        </div>
    </div>
</header>