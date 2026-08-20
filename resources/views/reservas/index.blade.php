@extends('layouts.app')
@section('titulo', 'Reservas')

@section('contenido')
    <h1>Reservas del {{ $fecha->format('d/m/Y') }}</h1>
    <p class="subtitulo">
        Agrupadas por ubicacion. La fecha es la del servicio: una reserva del
        sabado a la noche aparece en el sabado aunque termine el domingo.
    </p>

    <div class="panel">
        <form method="GET" action="{{ route('reservas.index') }}" class="en-linea">
            <div class="campo">
                <label for="fecha">Fecha</label>
                <input id="fecha" type="date" name="fecha" value="{{ $fecha->toDateString() }}">
            </div>
            <div class="campo">
                <button type="submit" class="secundario">Ver</button>
            </div>
            <div class="campo" style="margin-left:auto">
                <a class="boton" href="{{ route('reservas.create', ['fecha' => $fecha->toDateString()]) }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Nueva reserva
                </a>
            </div>
        </form>
        <p class="pie-nota">
            Tambien disponible como JSON:
            <a href="{{ route('api.reservas', ['fecha' => $fecha->toDateString()]) }}">/api/reservas?fecha={{ $fecha->toDateString() }}</a>
        </p>
    </div>

    @forelse ($grupos as $titulo => $reservas)
        <div class="panel" style="--i: {{ $loop->index + 1 }}">
            <h2>
                {{ $titulo }}
                <span class="etiqueta">{{ $reservas->count() }} reserva(s)</span>
            </h2>
            <div class="tabla-scroll">
                <table>
                    <thead>
                    <tr>
                        <th>Horario</th>
                        <th class="numerico">Personas</th>
                        <th>Mesas</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($reservas as $reserva)
                        <tr style="--i: {{ $loop->index }}">
                            <td>
                                {{ substr($reserva->starts_at, 11, 5) }} a {{ substr($reserva->ends_at, 11, 5) }}
                                @if (substr($reserva->starts_at, 0, 10) !== substr($reserva->ends_at, 0, 10))
                                    <span class="etiqueta">(termina el {{ \Carbon\Carbon::parse($reserva->ends_at)->format('d/m') }})</span>
                                @endif
                            </td>
                            <td class="numerico">{{ $reserva->cantidad_personas }}</td>
                            <td>{{ $reserva->mesas }}</td>
                            <td style="text-align:right">
                                <form method="POST" action="{{ route('reservas.destroy', $reserva->reserva_id) }}">
                                    @csrf @method('DELETE')
                                    <button class="peligro chico">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <line x1="5" y1="5" x2="19" y2="19"/><line x1="19" y1="5" x2="5" y2="19"/>
                                        </svg>
                                        Cancelar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="panel">
            <p class="vacio">No hay reservas para esta fecha.</p>
        </div>
    @endforelse
@endsection
