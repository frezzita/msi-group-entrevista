<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('msi-theme');
                if (t === 'light' || t === 'dark') document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Reservas') · MSI Group</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="barra">
    <div class="barra-interior">
        <a class="marca" href="{{ auth()->check() ? route('reservas.index') : url('/') }}">MSI Group</a>

        @auth
            <nav>
                <a href="{{ route('mesas.index') }}" class="{{ request()->routeIs('mesas.*') ? 'activo' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                        <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                    </svg>
                    Mesas
                </a>
                <a href="{{ route('reservas.index') }}" class="{{ request()->routeIs('reservas.*') ? 'activo' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="17" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/>
                    </svg>
                    Reservas
                </a>
                <a href="{{ route('estado.index') }}" class="{{ request()->routeIs('estado.*') ? 'activo' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2 12h5l2-7 4 14 2-7h7"/>
                    </svg>
                    Estado
                </a>
            </nav>
        @endauth

        <div class="barra-acciones">
            <button type="button" id="boton-tema" class="secundario chico" aria-label="Cambiar tema" title="Cambiar tema">
                <svg class="icono-sol" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"/>
                    <line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/>
                    <line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/>
                    <line x1="4.93" y1="4.93" x2="7.05" y2="7.05"/><line x1="16.95" y1="16.95" x2="19.07" y2="19.07"/>
                    <line x1="4.93" y1="19.07" x2="7.05" y2="16.95"/><line x1="16.95" y1="7.05" x2="19.07" y2="4.93"/>
                </svg>
                <svg class="icono-luna" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5Z"/>
                </svg>
            </button>

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="secundario chico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"/>
                            <polyline points="15 8 19 12 15 16"/><line x1="19" y1="12" x2="9" y2="12"/>
                        </svg>
                        Salir
                    </button>
                </form>
            @endauth
        </div>
    </div>
</header>

<main class="contenido">
    @if (session('ok'))
        <div class="aviso ok">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="aviso error">{{ session('error') }}</div>
    @endif

    @yield('contenido')
</main>

<script>
    (function () {
        var boton = document.getElementById('boton-tema');
        if (!boton) return;
        function actual() {
            return document.documentElement.getAttribute('data-theme')
                || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        }
        boton.addEventListener('click', function () {
            var siguiente = actual() === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', siguiente);
            try { localStorage.setItem('msi-theme', siguiente); } catch (e) {}
        });
    })();
</script>
</body>
</html>
