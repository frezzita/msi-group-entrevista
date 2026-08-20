@extends('layouts.app')
@section('titulo', 'Estado')

@section('contenido')
    <h1>Estado del salon</h1>
    <p class="subtitulo">
        Ocupacion en este momento, servicio del <span id="fecha-servicio">{{ $estado['fecha_servicio'] }}</span>.
        Se actualiza sola cada 15 segundos · ultima lectura <span id="actualizado">{{ $estado['actualizado'] }}</span>
    </p>

    <div id="estado">
        @include('estado._ubicaciones', ['ubicaciones' => $estado['ubicaciones']])
    </div>

    <script>
        // Refresco periodico contra el endpoint JSON. El evento ReservaCreada ya se
        // emite del lado del servidor: para tiempo real estricto se reemplaza este
        // intervalo por una suscripcion de broadcasting. Ver STACK.md.
        const contenedor = document.getElementById('estado');

        async function refrescar() {
            const respuesta = await fetch('{{ route('api.estado') }}', {headers: {'Accept': 'application/json'}});
            if (!respuesta.ok) return;

            const datos = await respuesta.json();
            document.getElementById('actualizado').textContent = datos.actualizado;
            document.getElementById('fecha-servicio').textContent = datos.fecha_servicio;

            contenedor.innerHTML = datos.ubicaciones.map(u => `
                <div class="panel">
                    <h2>Ubicacion ${u.ubicacion}
                        <span class="etiqueta">${u.seccion} · ${u.libres} de ${u.total} libres</span>
                    </h2>
                    <div class="grilla-mesas">
                        ${u.mesas.map(m => `
                            <div class="mesa-estado ${m.ocupada ? 'ocupada' : ''}">
                                <div class="numero">Mesa ${m.numero}</div>
                                <div class="detalle">${m.capacidad} lugares</div>
                                <div class="detalle">${m.ocupada ? 'ocupada hasta ' + m.hasta : 'libre'}</div>
                            </div>`).join('')}
                    </div>
                </div>`).join('');
        }

        setInterval(refrescar, 15000);
    </script>
@endsection
