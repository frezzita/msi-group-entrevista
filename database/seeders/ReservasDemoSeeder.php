<?php

namespace Database\Seeders;

use App\Models\Reserva;
use App\Models\User;
use App\Services\Reservas\Franja;
use App\Services\Reservas\ReservaService;
use App\Services\Reservas\VentanaServicio;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Reservas de demostracion, para que el listado del punto 4 y la pantalla de estado
 * muestren datos apenas se entra en vez de una tabla vacia.
 *
 * Usa la asignacion real (ReservaService::asignar) en vez de insertar filas a mano,
 * asi los datos sembrados son necesariamente coherentes con las reglas del sistema.
 * Se saltea la validacion de anticipacion a proposito: el seeder tiene que funcionar
 * a cualquier hora del dia, incluso si la ventana de hoy ya paso.
 */
class ReservasDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Reserva::exists()) {
            return;
        }

        $service = app(ReservaService::class);
        $user = User::firstOrFail();

        $hoy = CarbonImmutable::today();

        $agenda = [
            // hoy: dos grupos, uno de ellos requiere unir mesas
            [$hoy, '+2 hours', 2],
            [$hoy, '+2 hours', 6],

            // manana: tres grupos, incluyendo uno que no entra en A y cae a otra zona
            [$hoy->addDay(), '+1 hour', 4],
            [$hoy->addDay(), '+1 hour', 9],
            [$hoy->addDay(), '+3 hours', 2],

            // proximo sabado: el caso que cruza medianoche (23:30 termina el domingo 01:30)
            [$hoy->next(CarbonImmutable::SATURDAY), '23:30', 6],
            [$hoy->next(CarbonImmutable::SATURDAY), '+0 hours', 4],
        ];

        foreach ($agenda as [$fechaServicio, $desplazamiento, $personas]) {
            $service->asignar(
                $user,
                $this->franja($fechaServicio, $desplazamiento),
                $personas
            );
        }
    }

    /**
     * Construye la franja a partir de la ventana real del dia, para no depender de
     * horarios hardcodeados que dejarian de ser validos si cambia la configuracion.
     */
    private function franja(CarbonImmutable $fechaServicio, string $desplazamiento): Franja
    {
        $ventana = VentanaServicio::paraFechaDeServicio($fechaServicio);

        $inicio = str_contains($desplazamiento, ':')
            ? $fechaServicio->setTimeFromTimeString($desplazamiento)
            : $ventana->apertura->modify($desplazamiento);

        // Si la hora pedida es anterior a la apertura, pertenece a la madrugada
        // siguiente (ventanas que cruzan medianoche, como la del sabado).
        if ($ventana->admiteHorasDespuesDeMedianoche() && $inicio < $ventana->apertura) {
            $inicio = $inicio->addDay();
        }

        $inicio = $inicio->min($ventana->ultimoInicio);

        return new Franja(
            fechaServicio: $fechaServicio,
            inicio: $inicio,
            fin: $inicio->addMinutes((int) config('reservas.duracion_minutos')),
        );
    }
}
