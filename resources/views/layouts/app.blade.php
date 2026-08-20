<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Reservas') · MSI Group</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@auth
    <header class="barra">
        <div class="barra-interior">
            <a class="marca" href="{{ route('reservas.index') }}">MSI Group</a>
            <nav>
                <a href="{{ route('mesas.index') }}" class="{{ request()->routeIs('mesas.*') ? 'activo' : '' }}">Mesas</a>
                <a href="{{ route('reservas.index') }}" class="{{ request()->routeIs('reservas.*') ? 'activo' : '' }}">Reservas</a>
                <a href="{{ route('estado.index') }}" class="{{ request()->routeIs('estado.*') ? 'activo' : '' }}">Estado</a>
            </nav>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="secundario chico">Salir</button>
            </form>
        </div>
    </header>
@endauth

<main class="contenido">
    @if (session('ok'))
        <div class="aviso ok">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="aviso error">{{ session('error') }}</div>
    @endif

    @yield('contenido')
</main>
</body>
</html>
