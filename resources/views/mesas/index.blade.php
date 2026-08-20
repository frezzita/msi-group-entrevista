@extends('layouts.app')
@section('titulo', 'Mesas')

@section('contenido')
    <h1>Mesas</h1>
    <p class="subtitulo">Alta, baja y modificacion del salon.</p>

    <div class="panel">
        <h2>Nueva mesa</h2>
        <form method="POST" action="{{ route('mesas.store') }}" class="en-linea">
            @csrf
            <div class="campo">
                <label for="ubicacion_id">Ubicacion</label>
                <select id="ubicacion_id" name="ubicacion_id" required>
                    @foreach ($ubicaciones as $ubicacion)
                        <option value="{{ $ubicacion->id }}" @selected(old('ubicacion_id') == $ubicacion->id)>
                            {{ $ubicacion->nombre }} — {{ $ubicacion->seccion->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="campo">
                <label for="numero">Numero</label>
                <input id="numero" type="number" name="numero" min="1" value="{{ old('numero') }}" required>
            </div>
            <div class="campo">
                <label for="capacidad">Cantidad de personas</label>
                <input id="capacidad" type="number" name="capacidad" min="1" value="{{ old('capacidad') }}" required>
            </div>
            <div class="campo">
                <button type="submit">Agregar</button>
            </div>
        </form>

        @if ($errors->any())
            <div class="aviso error">
                @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
            </div>
        @endif
    </div>

    @foreach ($ubicaciones as $ubicacion)
        <div class="panel">
            <h2>
                Ubicacion {{ $ubicacion->nombre }}
                <span class="etiqueta">
                    {{ $ubicacion->seccion->nombre }} · {{ $ubicacion->mesas->count() }} mesas ·
                    {{ $ubicacion->mesas->sum('capacidad') }} lugares
                </span>
            </h2>

            @if ($ubicacion->mesas->isEmpty())
                <p class="vacio">Sin mesas cargadas.</p>
            @else
                <table>
                    <thead>
                    <tr>
                        <th>Mesa</th>
                        <th class="numerico">Capacidad</th>
                        <th style="width: 170px"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($ubicacion->mesas as $mesa)
                        <tr>
                            <td>{{ $mesa->numero }}</td>
                            <td class="numerico">{{ $mesa->capacidad }}</td>
                            <td style="text-align: right">
                                <a class="boton secundario chico" href="{{ route('mesas.edit', $mesa) }}">Editar</a>
                                <form method="POST" action="{{ route('mesas.destroy', $mesa) }}" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button class="peligro chico">Dar de baja</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
@endsection
