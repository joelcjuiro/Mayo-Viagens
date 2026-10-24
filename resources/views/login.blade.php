<!DOCTYPE html>
@php $encendido = $errors->any() || session('status'); @endphp
<html lang="es" class="{{ $encendido ? 'on' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Iniciar sesión | Mayo Viagens</title>
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#0b0807">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;1,9..144,400&family=Mulish:wght@400;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;1,9..144,400&family=Mulish:wght@400;600;700;800&display=swap"></noscript>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    {{-- Sin JavaScript la lámpara arranca encendida para poder entrar --}}
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="auth-body">
    <div class="dust" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>

    <a class="brand" href="{{ route('inicio') }}">
        <img src="{{ asset('images/logo.png') }}" alt="" width="28" height="28" onerror="this.remove()">
        <span>Mayo Viagens</span>
    </a>

    <main class="stage">
        {{-- ============ Lámpara ============ --}}
        <section class="scene" aria-label="Lámpara">
            <div class="room-glow" aria-hidden="true"></div>

            <div class="lamp">
                <div class="cone" aria-hidden="true"></div>
                <svg class="lamp-svg" viewBox="0 0 200 520" aria-hidden="true">
                    <defs>
                        <linearGradient id="shadeOff" x1="0" x2="1"><stop offset="0" stop-color="#1b1816"/><stop offset=".5" stop-color="#2b2623"/><stop offset="1" stop-color="#171413"/></linearGradient>
                        <linearGradient id="shadeOn" x1="0" x2="1"><stop offset="0" stop-color="#e0a45f"/><stop offset=".5" stop-color="#ffd9a0"/><stop offset="1" stop-color="#d9964d"/></linearGradient>
                        <linearGradient id="pole" x1="0" x2="1"><stop offset="0" stop-color="#15110f"/><stop offset=".5" stop-color="#4a413b"/><stop offset="1" stop-color="#15110f"/></linearGradient>
                        <radialGradient id="bulb"><stop offset="0" stop-color="#fff6df"/><stop offset="1" stop-color="#ffc678"/></radialGradient>
                    </defs>
                    <ellipse cx="100" cy="500" rx="46" ry="9" fill="#000" opacity=".55"/>
                    <ellipse cx="100" cy="490" rx="40" ry="9" fill="#26201d"/>
                    <path d="M60 490v-10c0-5 18-9 40-9s40 4 40 9v10c0 5-18 9-40 9s-40-4-40-9z" fill="#1c1715"/>
                    <rect x="96.5" y="140" width="7" height="340" fill="url(#pole)"/>
                    <rect x="94" y="330" width="12" height="10" rx="2" fill="#a37a3c" class="collar"/>
                    <path d="M62 40h76l32 92H30z" fill="url(#shadeOff)"/>
                    <path class="shade-on" d="M62 40h76l32 92H30z" fill="url(#shadeOn)"/>
                    <ellipse cx="100" cy="132" rx="70" ry="8" class="rim"/>
                    <ellipse cx="100" cy="140" rx="12" ry="14" class="bulb" fill="url(#bulb)"/>
                </svg>
                <button type="button" class="cord" id="cord" aria-pressed="{{ $encendido ? 'true' : 'false' }}" aria-label="Jalar la cadena para encender la lámpara">
                    <span class="cord-line"></span><span class="cord-bead"></span>
                </button>
            </div>

            <p class="hint" id="hint">Jala la cadena para entrar</p>
        </section>

        {{-- ============ Formulario ============ --}}
        <section class="card-wrap" aria-label="Iniciar sesión">
            <div class="card" id="card">
                <p class="eyebrow">Acceso privado</p>
                <h1>Bienvenido de vuelta.<br><em>Tus viajes te esperan.</em></h1>

                <form method="POST" action="{{ route('login.post') }}" id="login-form" novalidate>
                    @csrf

                    @if (session('status'))<div class="alert ok" role="status">{{ session('status') }}</div>@endif
                    @if ($errors->any())<div class="alert" role="alert">{{ $errors->first() }}</div>@endif

                    <div class="field">
                        <label for="email">Correo</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="tu@correo.com"
                               autocomplete="username" @if ($errors->any()) aria-invalid="true" @endif required>
                    </div>

                    <div class="field">
                        <label for="password">Contraseña</label>
                        <div class="pw">
                            <input id="password" type="password" name="password" placeholder="••••••••"
                                   autocomplete="current-password" @if ($errors->any()) aria-invalid="true" @endif required>
                            <button type="button" class="toggle" id="toggle" aria-label="Mostrar contraseña">Mostrar</button>
                        </div>
                    </div>

                    <div class="auth-row">
                        <label class="check">
                            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                            <span>Mantener sesión</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn" id="submit">Ingresar</button>
                </form>

                <p class="back"><a href="{{ route('inicio') }}">← Volver al sitio</a></p>
            </div>
        </section>
    </main>

    <script>
    (function () {
        var root = document.documentElement;
        var cord = document.getElementById('cord');
        var hint = document.getElementById('hint');
        var card = document.getElementById('card');
        var email = document.getElementById('email');
        var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

        function setOn(on, focus) {
            root.classList.toggle('on', on);
            cord.setAttribute('aria-pressed', on ? 'true' : 'false');
            cord.setAttribute('aria-label', on ? 'Jalar la cadena para apagar la lámpara' : 'Jalar la cadena para encender la lámpara');
            hint.textContent = on ? 'Jala de nuevo para apagar' : 'Jala la cadena para entrar';
            if (on && focus) { setTimeout(function () { email.focus({ preventScroll: true }); }, reduce ? 0 : 650); }
        }

        // Cadena: clic, tecla o arrastre hacia abajo
        var startY = null, moved = 0;
        cord.addEventListener('pointerdown', function (e) {
            startY = e.clientY; moved = 0; cord.setPointerCapture(e.pointerId); cord.classList.add('pulling');
        });
        cord.addEventListener('pointermove', function (e) {
            if (startY === null) { return; }
            moved = Math.max(0, Math.min(34, e.clientY - startY));
            cord.style.setProperty('--pull', moved + 'px');
        });
        function release() {
            if (startY === null) { return; }
            startY = null; cord.classList.remove('pulling'); cord.style.setProperty('--pull', '0px');
            setOn(!root.classList.contains('on'), true);
        }
        cord.addEventListener('pointerup', release);
        cord.addEventListener('pointercancel', function () { startY = null; cord.classList.remove('pulling'); cord.style.setProperty('--pull', '0px'); });
        cord.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                cord.classList.add('pulling'); cord.style.setProperty('--pull', '18px');
                setTimeout(function () { cord.classList.remove('pulling'); cord.style.setProperty('--pull', '0px'); setOn(!root.classList.contains('on'), true); }, 120);
            }
        });

        // Si ya empiezan a escribir (autocompletado del navegador), enciende sola
        email.addEventListener('focus', function () { if (!root.classList.contains('on')) { setOn(true, false); } });

        // Si llega con error (o mensaje), la lámpara ya está encendida: foco donde corresponde
        if (root.classList.contains('on')) {
            setOn(true, false);
            (document.querySelector('.alert[role=alert]') ? document.getElementById('password') : email).focus({ preventScroll: true });
        }

        // Mostrar / ocultar contraseña
        var pass = document.getElementById('password'), toggle = document.getElementById('toggle');
        toggle.addEventListener('click', function () {
            var show = pass.type === 'password';
            pass.type = show ? 'text' : 'password';
            toggle.textContent = show ? 'Ocultar' : 'Mostrar';
            toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });

        document.getElementById('login-form').addEventListener('submit', function () {
            var b = document.getElementById('submit'); b.disabled = true; b.textContent = 'Ingresando…';
        });
    })();
    </script>
</body>
</html>
