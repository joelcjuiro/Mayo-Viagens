<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Mayo Viagens | Cusco, Peru')</title>
    <meta name="description" content="@yield('description', 'Agência local em Cusco com guias em português. Machu Picchu, Vale Sagrado, Montanha 7 Cores e mais.')">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#0b0806">
    @if(trim(View::yieldContent('keywords')) !== '')<meta name="keywords" content="@yield('keywords')">@endif
    @php $ogImg = trim(View::yieldContent('og_image')) ?: (glob(public_path('img/og.*')) ? asset('img/'.basename(glob(public_path('img/og.*'))[0])) : null); @endphp
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Mayo Viagens">
    <meta property="og:title" content="@yield('title', 'Mayo Viagens | Cusco, Peru')">
    <meta property="og:description" content="@yield('description', 'Agência local em Cusco com guias em português. Machu Picchu, Vale Sagrado, Montanha 7 Cores e mais.')">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($ogImg)<meta property="og:image" content="{{ $ogImg }}">@endif
    <meta name="twitter:card" content="{{ $ogImg ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="@yield('title', 'Mayo Viagens | Cusco, Peru')">
    @if($ogImg)<meta name="twitter:image" content="{{ $ogImg }}">@endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700;1,9..144,500&family=Mulish:wght@400;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700;1,9..144,500&family=Mulish:wght@400;600;700;800&display=swap"></noscript>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
</head>
<body>
    @include('partials.header')
    <main>@yield('content')</main>
    @include('partials.footer')

    @php $whatsFloat = config('services.whatsapp', '51944845870'); @endphp
    <div class="wa-float">
        <div class="wa-bubble" data-wa-bubble>
            <span>Estamos online!</span>
            <button type="button" aria-label="Fechar" data-wa-close>×</button>
        </div>
        <a class="wa-btn" href="https://wa.me/{{ $whatsFloat }}" target="_blank" rel="noopener" aria-label="Falar no WhatsApp">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2Zm5.8 14.2c-.2.7-1.4 1.3-2 1.4-.5.1-1.2.1-1.9-.1-.4-.1-1-.3-1.7-.6-3-1.3-4.9-4.3-5.1-4.5-.1-.2-1.2-1.6-1.2-3s.8-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .5l-.4.6-.3.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.1 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l2 .9c.3.1.5.2.6.3.1.2.1.8-.1 1.5Z"/></svg>
        </a>
    </div>

    <script src="{{ asset('js/main.js') }}" defer></script>
    @stack('scripts')
</body>
</html>