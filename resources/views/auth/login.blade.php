@extends('layouts.app')
@section('titulo', 'Ingresar')

@section('contenido')
    <div class="centrado">
        <h1>MSI Group</h1>
        <p class="subtitulo">Sistema de reservas</p>

        <div class="panel">
            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="campo">
                    <label for="email">Correo</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                    @error('email') <span class="error-campo">{{ $message }}</span> @enderror
                </div>

                <div class="campo">
                    <label for="password">Contrasena</label>
                    <input id="password" type="password" name="password" required>
                    @error('password') <span class="error-campo">{{ $message }}</span> @enderror
                </div>

                <button type="submit">Ingresar</button>
            </form>
        </div>

        <p class="pie-nota">Sin cuenta todavia? <a href="{{ route('register') }}">Crear una</a></p>
    </div>
@endsection
