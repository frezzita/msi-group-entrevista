@extends('layouts.app')
@section('titulo', 'Nueva reserva')

@section('contenido')
    <h1>Nueva reserva</h1>
    <p class="subtitulo">La ubicacion y las mesas las asigna el sistema.</p>

    <div class="panel" style="max-width: 520px">
        <form method="POST" action="{{ route('reservas.store') }}">
            @csrf

            <div class="campo">
                <label for="fecha">Fecha (noche de servicio)</label>
                <input id="fecha" type="date" name="fecha" value="{{ old('fecha', $fecha->toDateString()) }}" required>
                @error('fecha') <span class="error-campo">{{ $message }}</span> @enderror
            </div>

            <div class="campo">
                <label for="hora">Hora</label>
                <select id="hora" name="hora" required>
                    @foreach ($horarios as $horario)
                        <option value="{{ $horario }}" @selected(old('hora') === $horario)>{{ $horario }}</option>
                    @endforeach
                </select>
                <span class="pie-nota" id="nota-horarios">
                    Solo se ofrecen horarios en los que la reserva de 2 horas entra completa.
                </span>
                @error('hora') <span class="error-campo">{{ $message }}</span> @enderror
            </div>

            <div class="campo">
                <label for="cantidad_personas">Cantidad de personas</label>
                <input id="cantidad_personas" type="number" name="cantidad_personas" min="1"
                       value="{{ old('cantidad_personas', 2) }}" required>
                @error('cantidad_personas') <span class="error-campo">{{ $message }}</span> @enderror
            </div>

            <button type="submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="4 12 9 17 20 6"/>
                </svg>
                Reservar
            </button>
            <a class="boton secundario" href="{{ route('reservas.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                </svg>
                Volver
            </a>
        </form>
    </div>

    <script>
        // Los horarios validos dependen del dia: el sabado va de 22:00 a 00:00 y el
        // domingo de 12:00 a 14:00. Al cambiar la fecha se repuebla el selector.
        const fecha = document.getElementById('fecha');
        const hora = document.getElementById('hora');

        fecha.addEventListener('change', async () => {
            const respuesta = await fetch(`{{ route('api.horarios') }}?fecha=${fecha.value}`);
            const datos = await respuesta.json();

            hora.innerHTML = '';
            for (const h of datos.horarios) {
                hora.add(new Option(h, h));
            }
        });
    </script>
@endsection
