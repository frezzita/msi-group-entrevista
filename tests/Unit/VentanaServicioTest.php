<?php

use App\Services\Reservas\VentanaServicio;
use Carbon\CarbonImmutable;

it('deriva la ventana de lunes a viernes: 10 a 24 con ultimo inicio 22:00', function () {
    $ventana = VentanaServicio::paraFechaDeServicio(CarbonImmutable::parse(MARTES));

    expect($ventana->apertura->format('Y-m-d H:i'))->toBe(MARTES.' 10:00')
        ->and($ventana->etiquetaCierre())->toBe('24:00')
        ->and($ventana->ultimoInicio->format('Y-m-d H:i'))->toBe(MARTES.' 22:00');
});

it('deriva la ventana del sabado: 22 a 02 con ultimo inicio a la medianoche', function () {
    $ventana = VentanaServicio::paraFechaDeServicio(CarbonImmutable::parse(SABADO));

    expect($ventana->apertura->format('Y-m-d H:i'))->toBe(SABADO.' 22:00')
        ->and($ventana->cierre->format('Y-m-d H:i'))->toBe('2026-08-30 02:00')
        // el ultimo inicio ya cae en el domingo del calendario
        ->and($ventana->ultimoInicio->format('Y-m-d H:i'))->toBe('2026-08-30 00:00');
});

it('deriva la ventana del domingo: 12 a 16 con ultimo inicio 14:00', function () {
    $ventana = VentanaServicio::paraFechaDeServicio(CarbonImmutable::parse(DOMINGO));

    expect($ventana->apertura->format('H:i'))->toBe('12:00')
        ->and($ventana->etiquetaCierre())->toBe('16:00')
        ->and($ventana->ultimoInicio->format('Y-m-d H:i'))->toBe(DOMINGO.' 14:00');
});

it('solo el sabado admite horarios despues de medianoche', function () {
    expect(VentanaServicio::paraFechaDeServicio(CarbonImmutable::parse(SABADO))->admiteHorasDespuesDeMedianoche())->toBeTrue()
        // de lunes a viernes el cierre es a las 24:00 pero el ultimo inicio son las
        // 22:00 del mismo dia: una hora de madrugada no pertenece a esa noche
        ->and(VentanaServicio::paraFechaDeServicio(CarbonImmutable::parse(MARTES))->admiteHorasDespuesDeMedianoche())->toBeFalse()
        ->and(VentanaServicio::paraFechaDeServicio(CarbonImmutable::parse(DOMINGO))->admiteHorasDespuesDeMedianoche())->toBeFalse();
});

it('arma la grilla de horarios del formulario terminando en el ultimo inicio', function () {
    $sabado = VentanaServicio::paraFechaDeServicio(CarbonImmutable::parse(SABADO))->horariosDeGrilla(30);

    expect($sabado)->toBe(['22:00', '22:30', '23:00', '23:30', '00:00']);

    $domingo = VentanaServicio::paraFechaDeServicio(CarbonImmutable::parse(DOMINGO))->horariosDeGrilla(60);

    expect($domingo)->toBe(['12:00', '13:00', '14:00']);
});
