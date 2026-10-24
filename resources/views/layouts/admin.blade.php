<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Panel') | Mayo Viagens</title>
    <meta name="robots" content="noindex,nofollow">
    <script>document.documentElement.classList.add('js')</script>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=Mulish:wght@400;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=Mulish:wght@400;600;700;800&display=swap"></noscript>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-tour.css') }}">
    @stack('styles')
</head>
<body class="admin">
    <aside class="sidebar" data-sidebar>
        <a href="{{ route('admin.index') }}" class="side-brand">Mayo <span>Viagens</span></a>
        <nav>
            <a href="{{ route('admin.index') }}" @class(['on' => request()->routeIs('admin.index')])>
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>Resumen</a>
            <a href="{{ route('admin.tours') }}" @class(['on' => request()->routeIs('admin.tours*')])>
                <svg viewBox="0 0 24 24"><path d="m3 20 6-11 4 6 3-4 5 9H3Z"/></svg>Tours</a>
            <a href="{{ route('admin.secciones') }}" @class(['on' => request()->routeIs('admin.secciones*')])>
                <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h10M4 18h16"/><circle cx="18" cy="12" r="2"/></svg>Secciones del inicio</a>
            @if(auth()->user()->esAdmin())
            <a href="{{ route('admin.usuarios') }}" @class(['on' => request()->routeIs('admin.usuarios*')])>
                <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6M17 11.5a3 3 0 1 0 0-6M18.5 14.5c2 .6 3 2.3 3 5"/></svg>Usuarios</a>
            @endif
            <a href="{{ route('admin.resenas') }}" @class(['on' => request()->routeIs('admin.resenas*')])>
                <svg viewBox="0 0 24 24"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z"/></svg>Opiniones
                @php $opPend = rescue(fn () => \App\Models\Resena::where('estado', 'pendiente')->count(), 0, false); @endphp
                @if($opPend)<span class="tag warn" style="margin-left:auto">{{ $opPend }}</span>@endif</a>
            <a href="{{ route('admin.reclamos') }}" @class(['on' => request()->routeIs('admin.reclamos*')])>
                <svg viewBox="0 0 24 24"><path d="M5 4h14v16H5z"/><path d="M9 9h6M9 13h6"/></svg>Libro de reclamaciones</a>
            <a href="{{ route('admin.reservas') }}" @class(['on' => request()->routeIs('admin.reservas*')])>
                <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>Reservas</a>
            <a href="{{ route('admin.contactos') }}" @class(['on' => request()->routeIs('admin.contactos*')])>
                <svg viewBox="0 0 24 24"><path d="M4 5h16v11H8l-4 4V5Z"/></svg>Mensajes</a>
            <a href="{{ route('inicio') }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24"><path d="M14 4h6v6M20 4l-9 9M18 14v5H5V6h5"/></svg>Ver sitio</a>
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="logout">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </aside>

    <div class="admin-main">
        <header class="topbar">
            <button class="burger" type="button" aria-label="Menú" data-sidebar-toggle><span></span><span></span><span></span></button>
            <div>
                <h1>@yield('heading', 'Panel')</h1>
                <p>@yield('subheading')</p>
            </div>
            <div class="who">
                <span class="avatar-sm">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                <span>{{ auth()->user()->name ?? 'Admin' }}</span>
            </div>
        </header>

        @if(session('success'))<div class="flash" role="status">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="flash flash-error" role="alert">{{ session('error') }}</div>@endif

        <div class="admin-content">@yield('content')</div>
    </div>

    <script>
        document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', function () {
            document.querySelector('[data-sidebar]').classList.toggle('open');
        });
    </script>
    <script>
    // Animaciones de entrada del panel (sin esto, las tarjetas con clase "panel" quedan ocultas por el CSS)
    (function () {
        var items = document.querySelectorAll('.panel, .kpi, .reveal');
        items.forEach(function (el) {
            el.querySelectorAll('.act, .dep, .rank, .todo li, .checks li, .legend li, tbody tr').forEach(function (row, i) { row.style.setProperty('--k', i); });
        });
        function show(el) { el.classList.add('in'); el.querySelectorAll('.bar').forEach(function (b) { b.classList.add('go'); }); }
        if (!('IntersectionObserver' in window)) { items.forEach(show); return; }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) { if (e.isIntersecting) { show(e.target); io.unobserve(e.target); } });
        }, { threshold: 0.08 });
        items.forEach(function (el) { io.observe(el); });
        setTimeout(function () { items.forEach(show); }, 2500); // respaldo
    })();
    </script>
    @stack('scripts')
</body>
</html>
