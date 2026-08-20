<?php

use App\Models\Mesa;
use App\Queries\ReservasPorFechaQuery;
use App\Services\Reservas\ReservaService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse(LUNES.' 12:00'));
    crearLocalDemo();
    $this->service = app(ReservaService::class);
    $this->query = app(ReservasPorFechaQuery::class);
    $this->user = usuario();
});

function reservarPara(string $fecha, string $hora, int $personas): \App\Models\Reserva
{
    return test()->service->crear(test()->user, CarbonImmutable::parse($fecha), $hora, $personas);
}

it('lista las reservas de la fecha con su seccion, ubicacion y mesas', function () {
    reservarPara(MARTES, '20:00', 2); // A, mesa 1
    reservarPara(MARTES, '20:00', 6); // A, mesas 2+3 unidas (la 1 ya esta tomada)

    $filas = $this->query->paraFecha(MARTES);

    expect($filas)->toHaveCount(2)
        ->and($filas->first()->seccion)->toBe('Salon')
        ->and($filas->first()->ubicacion)->toBe('A')
        ->and($filas->first()->mesas)->toBe('1')
        ->and($filas->last()->mesas)->toBe('2, 3')       // mesas unidas, concatenadas y ordenadas
        ->and($filas->last()->cantidad_personas)->toBe(6);
});

it('resuelve el listado en una sola consulta', function () {
    reservarPara(MARTES, '20:00', 2);
    reservarPara(MARTES, '20:00', 6);
    reservarPara(MARTES, '20:00', 9); // cae en B: obliga a cruzar mas de una ubicacion

    // A partir de aca no puede haber mas de una consulta. Se ejercita la clase de
    // consulta directamente y no una request autenticada, porque la sesion y el
    // usuario sumarian sus propias consultas al conteo.
    $this->expectsDatabaseQueryCount(1);

    $filas = $this->query->paraFecha(MARTES);

    expect($filas)->toHaveCount(3);
});

it('agrupa por ubicacion sin consultas adicionales', function () {
    reservarPara(MARTES, '20:00', 2);
    reservarPara(MARTES, '20:00', 9);

    $this->expectsDatabaseQueryCount(1);

    $grupos = $this->query->agrupadasPorUbicacion(MARTES);

    expect($grupos->keys()->all())->toBe(['Salon / A', 'Salon / B']);
});

it('incluye en el sabado la reserva que termina el domingo', function () {
    $reserva = reservarPara(SABADO, '23:30', 4);
    expect($reserva->ends_at->toDateString())->toBe('2026-08-30'); // termina el domingo

    expect($this->query->paraFecha(SABADO))->toHaveCount(1)
        // y no aparece en el domingo, que es un servicio distinto
        ->and($this->query->paraFecha('2026-08-30'))->toHaveCount(0);
});

it('no incluye reservas de otras fechas', function () {
    reservarPara(MARTES, '20:00', 2);

    expect($this->query->paraFecha(LUNES))->toHaveCount(0);
});

it('no incluye reservas canceladas', function () {
    $reserva = reservarPara(MARTES, '20:00', 2);
    expect($this->query->paraFecha(MARTES))->toHaveCount(1);

    $this->service->cancelar($reserva);

    expect($this->query->paraFecha(MARTES))->toHaveCount(0);
});

it('conserva las mesas de las reservas historicas aunque la mesa se de de baja', function () {
    reservarPara(MARTES, '20:00', 2);

    Mesa::where('numero', 1)->firstOrFail()->delete(); // baja logica

    // el historico no se toca: la reserva sigue mostrando en que mesa fue
    expect($this->query->paraFecha(MARTES)->first()->mesas)->toBe('1');
});
