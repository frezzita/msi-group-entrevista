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
        //
        // El parche es en el lugar (no innerHTML) para poder animar solo la mesa que
        // cambio de estado, y para que la animacion de entrada de los paneles no se
        // repita en cada poll.
        const contenedor = document.getElementById('estado');

        const iconoLibre = '<svg viewBox="0 0 12 12" aria-hidden="true"><circle cx="6" cy="6" r="4" fill="currentColor"/></svg>';
        const iconoOcupada = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>';

        function pildoraHtml(mesa) {
            return (mesa.ocupada ? iconoOcupada : iconoLibre)
                + (mesa.ocupada ? 'ocupada hasta ' + mesa.hasta : 'libre');
        }

        function crearMesaEstado(ubicacion, mesa) {
            const div = document.createElement('div');
            div.className = 'mesa-estado' + (mesa.ocupada ? ' ocupada' : '');
            div.dataset.clave = `${ubicacion}|${mesa.numero}`;
            div.dataset.ocupada = mesa.ocupada ? '1' : '0';
            div.innerHTML = `
                <div class="numero">Mesa ${mesa.numero}</div>
                <div class="detalle">${mesa.capacidad} lugares</div>
                <span class="pildora ${mesa.ocupada ? 'ocupada' : 'libre'}">${pildoraHtml(mesa)}</span>`;
            return div;
        }

        function crearPanelUbicacion(u, indice) {
            const panel = document.createElement('div');
            panel.className = 'panel';
            panel.dataset.ubicacion = u.ubicacion;
            panel.style.setProperty('--i', indice);
            panel.innerHTML = `
                <h2>Ubicacion ${u.ubicacion}<span class="etiqueta">${u.libres} de ${u.total} libres</span></h2>
                <div class="grilla-mesas"></div>`;
            const grilla = panel.querySelector('.grilla-mesas');
            u.mesas.forEach(m => grilla.appendChild(crearMesaEstado(u.ubicacion, m)));
            return panel;
        }

        async function refrescar() {
            const respuesta = await fetch('{{ route('api.estado') }}', {headers: {'Accept': 'application/json'}});
            if (!respuesta.ok) return;

            const datos = await respuesta.json();
            document.getElementById('actualizado').textContent = datos.actualizado;
            document.getElementById('fecha-servicio').textContent = datos.fecha_servicio;

            datos.ubicaciones.forEach((u, indice) => {
                let panel = contenedor.querySelector(`.panel[data-ubicacion="${CSS.escape(u.ubicacion)}"]`);
                if (!panel) { panel = crearPanelUbicacion(u, indice); contenedor.appendChild(panel); return; }
                panel.querySelector('.etiqueta').textContent = `${u.libres} de ${u.total} libres`;

                u.mesas.forEach(m => {
                    const clave = `${u.ubicacion}|${m.numero}`;
                    let el = panel.querySelector(`.mesa-estado[data-clave="${CSS.escape(clave)}"]`);
                    const previa = el ? el.dataset.ocupada === '1' : null;
                    if (!el) { el = crearMesaEstado(u.ubicacion, m); panel.querySelector('.grilla-mesas').appendChild(el); return; }

                    el.dataset.ocupada = m.ocupada ? '1' : '0';
                    el.classList.toggle('ocupada', m.ocupada);
                    el.querySelector('.pildora').className = 'pildora ' + (m.ocupada ? 'ocupada' : 'libre');
                    el.querySelector('.pildora').innerHTML = pildoraHtml(m);

                    if (previa !== null && previa !== m.ocupada) {
                        el.classList.remove('actualizada');
                        void el.offsetWidth;
                        el.classList.add('actualizada');
                    }
                });
            });
        }

        contenedor.addEventListener('animationend', (evento) => {
            if (evento.animationName === 'destello') evento.target.classList.remove('actualizada');
        });

        setInterval(refrescar, 15000);
    </script>
@endsection
