<?php

namespace App\Services\Reservas;

use Carbon\CarbonImmutable;

/**
 * Intervalo concreto que ocuparia una reserva, ya resuelto y validado.
 *
 * fechaServicio es el dia de negocio y no siempre coincide con la fecha de
 * calendario de $inicio: una reserva del sabado a las 00:00 arranca el domingo
 * pero pertenece al servicio del sabado.
 */
final class Franja
{
    public function __construct(
        public readonly CarbonImmutable $fechaServicio,
        public readonly CarbonImmutable $inicio,
        public readonly CarbonImmutable $fin,
    ) {}

    public function cruzaMedianoche(): bool
    {
        return ! $this->inicio->isSameDay($this->fin);
    }
}
