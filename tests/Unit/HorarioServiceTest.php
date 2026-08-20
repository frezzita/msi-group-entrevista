<?php

use App\Exceptions\FueraDePlazoException;
use App\Exceptions\HorarioNoDisponibleException;
use App\Services\Reservas\HorarioService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    // Lunes al mediodia: todas las fechas de referencia quedan en el futuro.
    $this->travelTo(CarbonImmutable::parse(LUNES.' 12:00'));
    $this->horarios = new HorarioService;
});

it('acepta un horario dentro de la ventana de un dia de semana', function () {
    $franja = $this->horarios->resolver(CarbonImmutable::parse(MARTES), '20:00');

    expect($franja->inicio->format('Y-m-d H:i'))->toBe(MARTES.' 20:00')
        ->and($franja->fin->format('Y-m-d H:i'))->toBe(MARTES.' 22:00')
        ->and($franja->fechaServicio->toDateString())->toBe(MARTES);
});

it('acepta el ultimo horario en el que la reserva entra completa', function () {
    $franja = $this->horarios->resolver(CarbonImmutable::parse(MARTES), '22:00');

    expect($franja->fin->format('Y-m-d H:i'))->toBe('2026-08-26 00:00'); // cierra justo a las 24:00
});

it('rechaza un horario cuyas dos horas se pasan del cierre', function () {
    // 23:00 esta dentro del horario del local (10 a 24) pero la reserva terminaria a la 01:00
    expect(fn () => $this->horarios->resolver(CarbonImmutable::parse(MARTES), '23:00'))
        ->toThrow(HorarioNoDisponibleException::class);

    try {
        $this->horarios->resolver(CarbonImmutable::parse(MARTES), '23:00');
    } catch (HorarioNoDisponibleException $e) {
        // el mensaje tiene que explicar la regla, no limitarse a negar
        expect($e->getMessage())->toContain('22:00')
            ->and($e->getMessage())->toContain('martes')
            ->and($e->getMessage())->toContain('24:00');
    }
});

it('rechaza un horario anterior a la apertura sin correrlo al dia siguiente', function () {
    expect(fn () => $this->horarios->resolver(CarbonImmutable::parse(MARTES), '09:00'))
        ->toThrow(HorarioNoDisponibleException::class, 'El martes atendemos de 10:00 a 24:00');
});

it('guarda la reserva del sabado a la noche dentro del servicio del sabado', function () {
    $franja = $this->horarios->resolver(CarbonImmutable::parse(SABADO), '23:30');

    expect($franja->inicio->format('Y-m-d H:i'))->toBe(SABADO.' 23:30')
        ->and($franja->fin->format('Y-m-d H:i'))->toBe('2026-08-30 01:30') // termina el domingo
        ->and($franja->fechaServicio->toDateString())->toBe(SABADO)        // pero es del sabado
        ->and($franja->cruzaMedianoche())->toBeTrue();
});

it('interpreta la medianoche del sabado como la madrugada del domingo', function () {
    $franja = $this->horarios->resolver(CarbonImmutable::parse(SABADO), '00:00');

    expect($franja->inicio->format('Y-m-d H:i'))->toBe('2026-08-30 00:00')
        ->and($franja->fin->format('Y-m-d H:i'))->toBe('2026-08-30 02:00') // justo hasta el cierre
        ->and($franja->fechaServicio->toDateString())->toBe(SABADO);
});

it('rechaza la 01:00 del sabado porque la reserva pasaria el cierre de las 2AM', function () {
    expect(fn () => $this->horarios->resolver(CarbonImmutable::parse(SABADO), '01:00'))
        ->toThrow(HorarioNoDisponibleException::class, 'El ultimo horario disponible para el sábado es las 00:00');
});

it('respeta la ventana corta del domingo', function () {
    expect($this->horarios->resolver(CarbonImmutable::parse(DOMINGO), '14:00')->fin->format('H:i'))->toBe('16:00');

    expect(fn () => $this->horarios->resolver(CarbonImmutable::parse(DOMINGO), '14:30'))
        ->toThrow(HorarioNoDisponibleException::class);
});

it('rechaza una reserva pedida con menos de 15 minutos de anticipacion', function () {
    $this->travelTo(CarbonImmutable::parse(MARTES.' 19:50'));

    expect(fn () => $this->horarios->resolver(CarbonImmutable::parse(MARTES), '20:00'))
        ->toThrow(FueraDePlazoException::class, '15 minutos');
});

it('acepta una reserva pedida justo con la anticipacion minima', function () {
    $this->travelTo(CarbonImmutable::parse(MARTES.' 19:45'));

    expect($this->horarios->resolver(CarbonImmutable::parse(MARTES), '20:00')->inicio->format('H:i'))->toBe('20:00');
});

/*
|--------------------------------------------------------------------------
| Dia de servicio en curso
|--------------------------------------------------------------------------
|
| No siempre es la fecha de hoy: la ventana del sabado llega hasta las 02:00 del
| domingo, asi que a la 01:00 de un domingo el servicio activo sigue siendo el del
| sabado. De esto depende que la pantalla de estado muestre la ocupacion correcta
| a la madrugada.
*/

it('durante la ventana del dia, el servicio en curso es el de hoy', function () {
    $this->travelTo(CarbonImmutable::parse(MARTES.' 20:00'));

    expect($this->horarios->fechaDeServicioActual()->toDateString())->toBe(MARTES);
});

it('a la madrugada del domingo el servicio en curso todavia es el del sabado', function () {
    $this->travelTo(CarbonImmutable::parse(DOMINGO.' 01:00'));

    expect($this->horarios->fechaDeServicioActual()->toDateString())->toBe(SABADO);
});

it('pasado el cierre del sabado, el servicio en curso ya es el del domingo', function () {
    // la ventana del sabado cierra a las 02:00
    $this->travelTo(CarbonImmutable::parse(DOMINGO.' 03:00'));

    expect($this->horarios->fechaDeServicioActual()->toDateString())->toBe(DOMINGO);
});

it('antes de abrir, el servicio en curso es el de hoy si la noche anterior ya cerro', function () {
    // sabado 21:00: falta una hora para abrir, y la ventana del viernes cerro a medianoche
    $this->travelTo(CarbonImmutable::parse(SABADO.' 21:00'));

    expect($this->horarios->fechaDeServicioActual()->toDateString())->toBe(SABADO);
});

it('a la manana temprano de un dia de semana el servicio en curso es el de ese dia', function () {
    $this->travelTo(CarbonImmutable::parse(MARTES.' 08:00'));

    expect($this->horarios->fechaDeServicioActual()->toDateString())->toBe(MARTES);
});
