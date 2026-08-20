@foreach ($ubicaciones as $u)
    <div class="panel" data-ubicacion="{{ $u['ubicacion'] }}" style="--i: {{ $loop->index }}">
        <h2>
            Ubicacion {{ $u['ubicacion'] }}
            <span class="etiqueta">{{ $u['libres'] }} de {{ $u['total'] }} libres</span>
        </h2>
        <div class="grilla-mesas">
            @foreach ($u['mesas'] as $mesa)
                <div class="mesa-estado {{ $mesa['ocupada'] ? 'ocupada' : '' }}"
                     data-clave="{{ $u['ubicacion'] }}|{{ $mesa['numero'] }}"
                     data-ocupada="{{ $mesa['ocupada'] ? '1' : '0' }}">
                    <div class="numero">Mesa {{ $mesa['numero'] }}</div>
                    <div class="detalle">{{ $mesa['capacidad'] }} lugares</div>
                    <span class="pildora {{ $mesa['ocupada'] ? 'ocupada' : 'libre' }}">
                        @if ($mesa['ocupada'])
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/>
                            </svg>
                        @else
                            <svg viewBox="0 0 12 12" aria-hidden="true"><circle cx="6" cy="6" r="4" fill="currentColor"/></svg>
                        @endif
                        {{ $mesa['ocupada'] ? 'ocupada hasta '.$mesa['hasta'] : 'libre' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
