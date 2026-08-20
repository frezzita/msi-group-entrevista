<?php

namespace App\Services\Reservas;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Ventana de servicio de un dia: cuando abre, cuando cierra y cual es el ultimo
 * horario en el que todavia entra una reserva completa.
 *
 * Se construye a partir de apertura + duracion de la ventana (no apertura + cierre)
 * porque el cierre de lunes a viernes es "24:00", que no es una hora valida, y el
 * del sabado son las 02:00 del dia siguiente, que como hora suelta parece anterior
 * a la apertura.
 */
final class VentanaServicio
{
    private function __construct(
        public readonly CarbonImmutable $apertura,
        public readonly CarbonImmutable $cierre,
        public readonly CarbonImmutable $ultimoInicio,
    ) {}

    public static function paraFechaDeServicio(CarbonInterface $fechaServicio): self
    {
        $horario = config('reservas.horarios.'.$fechaServicio->dayOfWeek);

        if (! is_array($horario)) {
            throw new InvalidArgumentException(
                'No hay horario de servicio configurado para el dia '.$fechaServicio->dayOfWeek
            );
        }

        $apertura = CarbonImmutable::instance($fechaServicio)
            ->startOfDay()
            ->setTimeFromTimeString($horario['apertura']);

        $cierre = $apertura->addMinutes((int) $horario['duracion_minutos']);

        return new self(
            apertura: $apertura,
            cierre: $cierre,
            ultimoInicio: $cierre->subMinutes((int) config('reservas.duracion_minutos')),
        );
    }

    /**
     * Si el ultimo inicio reservable cae en el dia siguiente, la ventana admite
     * horarios de madrugada (el caso del sabado) y una hora anterior a la apertura
     * debe interpretarse como parte de esa noche, no del amanecer del mismo dia.
     *
     * De lunes a viernes el cierre es a las 24:00 pero el ultimo inicio son las
     * 22:00 del mismo dia, asi que esto da false y las 09:00 se rechazan en vez
     * de rodar al dia siguiente.
     */
    public function admiteHorasDespuesDeMedianoche(): bool
    {
        return ! $this->ultimoInicio->isSameDay($this->apertura);
    }

    public function contiene(CarbonInterface $inicio): bool
    {
        return $inicio >= $this->apertura && $inicio <= $this->ultimoInicio;
    }

    /** "24:00" en vez de "00:00" cuando el cierre es la medianoche. */
    public function etiquetaCierre(): string
    {
        return $this->cierre->format('H:i') === '00:00' && ! $this->cierre->isSameDay($this->apertura)
            ? '24:00'
            : $this->cierre->format('H:i');
    }

    public function descripcion(): string
    {
        return 'de '.$this->apertura->format('H:i').' a '.$this->etiquetaCierre();
    }

    /**
     * Horarios que ofrece el selector del formulario. Es comodidad de interfaz:
     * el backend acepta cualquier hora dentro de la ventana, no solo estas.
     *
     * @return list<string>
     */
    public function horariosDeGrilla(?int $pasoMinutos = null): array
    {
        $paso = $pasoMinutos ?? (int) config('reservas.paso_grilla_minutos');
        $horarios = [];

        for ($hora = $this->apertura; $hora <= $this->ultimoInicio; $hora = $hora->addMinutes($paso)) {
            $horarios[] = $hora->format('H:i');
        }

        return $horarios;
    }
}
