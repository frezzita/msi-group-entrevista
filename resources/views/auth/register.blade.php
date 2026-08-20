@extends('layouts.app')
@section('titulo', 'Crear cuenta')

@section('contenido')
    <div class="centrado">
        <h1>MSI Group</h1>
        <p class="subtitulo">Crear cuenta</p>

        <div class="panel">
            <form method="POST" action="{{ route('register.store') }}">
                @csrf

                <div class="campo">
                    <label for="name">Nombre</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
                    @error('name') <span class="error-campo">{{ $message }}</span> @enderror
                </div>

                <div class="campo">
                    <label for="email">Correo</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                    @error('email') <span class="error-campo">{{ $message }}</span> @enderror
                </div>

                <div class="campo">
                    <label for="password">Contrasena</label>
                    <input id="password" type="password" name="password" required>
                    @error('password') <span class="error-campo">{{ $message }}</span> @enderror
                </div>

                <div class="campo">
                    <label for="password_confirmation">Repetir contrasena</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
                </div>

                <button type="submit">Crear cuenta</button>
            </form>
        </div>

        <p class="pie-nota">Ya tenes cuenta? <a href="{{ route('login') }}">Ingresar</a></p>
    </div>
@endsection
