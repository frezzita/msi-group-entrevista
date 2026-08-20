@extends('layouts.app')
@section('titulo', 'Editar mesa')

@section('contenido')
    <h1>Editar mesa {{ $mesa->numero }}</h1>
    <p class="subtitulo">Cambiar la capacidad no modifica las reservas ya asignadas.</p>

    <div class="panel" style="max-width: 460px">
        <form method="POST" action="{{ route('mesas.update', $mesa) }}">
            @csrf @method('PUT')

            <div class="campo">
                <label for="ubicacion_id">Ubicacion</label>
                <select id="ubicacion_id" name="ubicacion_id" required>
                    @foreach ($ubicaciones as $ubicacion)
                        <option value="{{ $ubicacion->id }}" @selected(old('ubicacion_id', $mesa->ubicacion_id) == $ubicacion->id)>
                            {{ $ubicacion->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="campo">
                <label for="numero">Numero</label>
                <input id="numero" type="number" name="numero" min="1" value="{{ old('numero', $mesa->numero) }}" required>
                @error('numero') <span class="error-campo">{{ $message }}</span> @enderror
            </div>

            <div class="campo">
                <label for="capacidad">Cantidad de personas</label>
                <input id="capacidad" type="number" name="capacidad" min="1" value="{{ old('capacidad', $mesa->capacidad) }}" required>
                @error('capacidad') <span class="error-campo">{{ $message }}</span> @enderror
            </div>

            <button type="submit">Guardar</button>
            <a class="boton secundario" href="{{ route('mesas.index') }}">Cancelar</a>
        </form>
    </div>
@endsection
