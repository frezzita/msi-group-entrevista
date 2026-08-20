@foreach ($ubicaciones as $u)
    <div class="panel">
        <h2>
            Ubicacion {{ $u['ubicacion'] }}
            <span class="etiqueta">{{ $u['seccion'] }} · {{ $u['libres'] }} de {{ $u['total'] }} libres</span>
        </h2>
        <div class="grilla-mesas">
            @foreach ($u['mesas'] as $mesa)
                <div class="mesa-estado {{ $mesa['ocupada'] ? 'ocupada' : '' }}">
                    <div class="numero">Mesa {{ $mesa['numero'] }}</div>
                    <div class="detalle">{{ $mesa['capacidad'] }} lugares</div>
                    <div class="detalle">{{ $mesa['ocupada'] ? 'ocupada hasta '.$mesa['hasta'] : 'libre' }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
