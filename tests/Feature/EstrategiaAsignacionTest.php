<?php

use App\Models\Reserva;
use App\Services\Reservas\EstrategiaAsignacion;
use App\Services\Reservas\ReservaService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse(LUNES.' 12:00'));
    $this->service = app(ReservaService::class);
    $this->user = usuario();
});

function conEstrategia(EstrategiaAsignacion $estrategia): void
{
    config()->set('reservas.estrategia_asignacion', $estrategia->value);
}

function reservarPersonas(int $personas, string $hora = '20:00'): Reserva
{
    return test()->service->crear(test()->user, CarbonImmutable::parse(MARTES), $hora, $personas);
}

it('con orden estricto gana la primera ubicacion con lugar aunque desperdicie asientos', function () {
    conEstrategia(EstrategiaAsignacion::OrdenEstricto);
    crearLocal([
        'A' => [1 => 4],          // unica opcion en A: sobra
        'B' => [2 => 2, 3 => 6],  // B tiene la mesa exacta
    ]);

    $reserva = reservarPersonas(2);

    expect($reserva->ubicacion->nombre)->toBe('A')
        ->and($reserva->mesas->pluck('numero')->all())->toBe([1]);
});

it('con ajuste exacto primero salta a la ubicacion que no desperdicia', function () {
    conEstrategia(EstrategiaAsignacion::AjusteExactoPrimero);
    crearLocal([
        'A' => [1 => 4],
        'B' => [2 => 2, 3 => 6],
    ]);

    $reserva = reservarPersonas(2);

    expect($reserva->ubicacion->nombre)->toBe('B')
        ->and($reserva->mesas->pluck('numero')->all())->toBe([2]);
});

it('con ajuste exacto primero no mira mas alla si la primera ubicacion ya es exacta', function () {
    conEstrategia(EstrategiaAsignacion::AjusteExactoPrimero);
    crearLocal([
        'A' => [1 => 2, 2 => 4],
        'B' => [3 => 2],
    ]);

    // el orden se sigue respetando: entre dos exactas gana la primera zona
    expect(reservarPersonas(2)->ubicacion->nombre)->toBe('A');
});

it('con ajuste exacto primero vuelve a la primera con lugar si ninguna es exacta', function () {
    conEstrategia(EstrategiaAsignacion::AjusteExactoPrimero);
    crearLocal([
        'A' => [1 => 4],
        'B' => [2 => 6],
    ]);

    // ninguna zona puede sentar 3 sin que sobre: se cae en la misma respuesta
    // que hubiera dado el orden estricto
    $reserva = reservarPersonas(3);

    expect($reserva->ubicacion->nombre)->toBe('A')
        ->and($reserva->mesas->pluck('numero')->all())->toBe([1]);
});

it('evita que un grupo chico queme la mesa grande y empuje al siguiente', function () {
    // El escenario completo: dos reservas encadenadas sobre el mismo local.
    $local = ['A' => [1 => 4], 'B' => [2 => 2, 3 => 6]];

    conEstrategia(EstrategiaAsignacion::OrdenEstricto);
    crearLocal($local);

    $dosPersonas = reservarPersonas(2);
    $cuatroPersonas = reservarPersonas(4);
    $desperdicioEstricto = ($dosPersonas->mesas->sum('capacidad') - 2)
        + ($cuatroPersonas->mesas->sum('capacidad') - 4);

    expect($desperdicioEstricto)->toBe(4); // 2 en la mesa de 4, y los 4 terminan en la de 6
})->group('comparacion');

it('con ajuste exacto primero el mismo escenario no desperdicia nada', function () {
    conEstrategia(EstrategiaAsignacion::AjusteExactoPrimero);
    crearLocal(['A' => [1 => 4], 'B' => [2 => 2, 3 => 6]]);

    $dosPersonas = reservarPersonas(2);   // B, mesa de 2
    $cuatroPersonas = reservarPersonas(4); // A, mesa de 4 que quedo libre

    $desperdicio = ($dosPersonas->mesas->sum('capacidad') - 2)
        + ($cuatroPersonas->mesas->sum('capacidad') - 4);

    expect($desperdicio)->toBe(0)
        ->and($dosPersonas->ubicacion->nombre)->toBe('B')
        ->and($cuatroPersonas->ubicacion->nombre)->toBe('A');
})->group('comparacion');

it('el proyecto se entrega priorizando el ajuste exacto', function () {
    // El enunciado dice "por orden" pero no define que hacer cuando la primera zona con
    // lugar solo puede ofrecer una mesa mas grande de la necesaria. Se opto por no
    // desperdiciar asientos; orden_estricto queda disponible por configuracion.
    expect(config('reservas.estrategia_asignacion'))->toBe(EstrategiaAsignacion::AjusteExactoPrimero->value);
});
