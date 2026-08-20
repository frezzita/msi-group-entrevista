<?php

namespace App\Services\Reservas;

use App\Exceptions\FueraDePlazoException;
use App\Exceptions\HorarioNoDisponibleException;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Traduce lo que carga el usuario (una fecha y una hora) al intervalo real que va
 * a ocupar la reserva, y rechaza lo que el local no puede atender.
 */
class HorarioService
{
    /**
     * @param  CarbonInterface  $fechaServicio  Fecha de servicio (la "noche"), no
     *                                          necesariamente la fecha de calendario del inicio.
     * @param  string  $hora  Hora de reloj en formato H:i.
     *
     * @throws HorarioNoDisponibleException
     * @throws FueraDePlazoException
     */
    public function resolver(CarbonInterface $fechaServicio, string $hora): Franja
    {
        $fechaServicio = CarbonImmutable::instance($fechaServicio)->startOfDay();
        $ventana = VentanaServicio::paraFechaDeServicio($fechaServicio);

        $inicio = $fechaServicio->setTimeFromTimeString($hora);

        // El sabado va de 22:00 a 02:00: una reserva "del sabado" a las 00:00 arranca
        // en realidad el domingo. La fecha que carga el usuario es la del servicio,
        // asi que las horas anteriores a la apertura pertenecen a la madrugada siguiente.
        if ($ventana->admiteHorasDespuesDeMedianoche() && $inicio < $ventana->apertura) {
            $inicio = $inicio->addDay();
        }

        $this->verificarVentana($inicio, $ventana, $fechaServicio);
        $this->verificarAnticipacion($inicio);

        return new Franja(
            fechaServicio: $fechaServicio,
            inicio: $inicio,
            fin: $inicio->addMinutes((int) config('reservas.duracion_minutos')),
        );
    }

    /**
     * Dia de servicio en curso.
     *
     * No siempre es la fecha de hoy: a la 01:00 de un domingo el servicio activo
     * todavia es el del sabado, porque su ventana llega hasta las 02:00.
     */
    public function fechaDeServicioActual(): CarbonImmutable
    {
        $hoy = CarbonImmutable::today();

        if (now() < VentanaServicio::paraFechaDeServicio($hoy)->apertura) {
            $ayer = $hoy->subDay();

            if (now() < VentanaServicio::paraFechaDeServicio($ayer)->cierre) {
                return $ayer;
            }
        }

        return $hoy;
    }

    /** Horarios que ofrece el formulario para una fecha de servicio dada. */
    public function horariosDisponibles(CarbonInterface $fechaServicio): array
    {
        return VentanaServicio::paraFechaDeServicio($fechaServicio)->horariosDeGrilla();
    }

    private function verificarVentana(
        CarbonImmutable $inicio,
        VentanaServicio $ventana,
        CarbonImmutable $fechaServicio
    ): void {
        if ($ventana->contiene($inicio)) {
            return;
        }

        $dia = $fechaServicio->locale('es')->dayName;

        if ($inicio < $ventana->apertura) {
            throw new HorarioNoDisponibleException(
                "El {$dia} atendemos {$ventana->descripcion()}: no se puede reservar a las ".
                $inicio->format('H:i').'.'
            );
        }

        // El caso interesante: la hora esta dentro del horario del local, pero la
        // reserva de 2 horas terminaria despues del cierre.
        throw new HorarioNoDisponibleException(
            "El ultimo horario disponible para el {$dia} es las ".
            $ventana->ultimoInicio->format('H:i').
            ' (la reserva dura '.(int) (config('reservas.duracion_minutos') / 60).
            " horas y el cierre es a las {$ventana->etiquetaCierre()})."
        );
    }

    private function verificarAnticipacion(CarbonImmutable $inicio): void
    {
        $minimo = (int) config('reservas.anticipacion_minutos');

        if ($inicio < now()->addMinutes($minimo)) {
            throw new FueraDePlazoException(
                "Las reservas se toman con al menos {$minimo} minutos de anticipacion."
            );
        }
    }
}
