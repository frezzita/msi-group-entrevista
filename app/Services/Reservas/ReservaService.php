<?php

namespace App\Services\Reservas;

use App\Events\ReservaCreada;
use App\Exceptions\ReservaNoPosibleException;
use App\Exceptions\SinDisponibilidadException;
use App\Models\Mesa;
use App\Models\Reserva;
use App\Models\Ubicacion;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Alta de reservas: valida el horario, elige ubicacion y mesas, y persiste.
 */
class ReservaService
{
    public function __construct(
        private readonly HorarioService $horarios,
        private readonly AsignadorDeMesas $asignador,
        private readonly DisponibilidadService $disponibilidad,
    ) {}

    /**
     * @throws ReservaNoPosibleException
     */
    public function crear(User $user, CarbonInterface $fechaServicio, string $hora, int $personas): Reserva
    {
        return $this->asignar($user, $this->horarios->resolver($fechaServicio, $hora), $personas);
    }

    /**
     * Asigna ubicacion y mesas para una franja ya resuelta y persiste la reserva.
     *
     * Separado de crear() porque la franja puede venir de otra parte que la entrada
     * del usuario (por ejemplo los seeders, que cargan datos historicos y de demo
     * sin pasar por la validacion de anticipacion).
     *
     * @throws SinDisponibilidadException
     */
    public function asignar(User $user, Franja $franja, int $personas): Reserva
    {
        return DB::transaction(function () use ($user, $franja, $personas) {
            [$ubicacion, $combinacion] = $this->elegirUbicacion($franja, $personas);

            if ($ubicacion !== null) {
                $reserva = Reserva::create([
                    'user_id' => $user->id,
                    'ubicacion_id' => $ubicacion->id,
                    'fecha_servicio' => $franja->fechaServicio->toDateString(),
                    'starts_at' => $franja->inicio,
                    'ends_at' => $franja->fin,
                    'cantidad_personas' => $personas,
                ]);

                $reserva->mesas()->attach($combinacion->ids());

                // Despues del commit, nunca antes: si se invalida la cache dentro de
                // la transaccion, un lector concurrente la repuebla con un snapshot
                // que todavia no ve esta reserva y deja datos viejos cacheados hasta
                // que venza el TTL.
                DB::afterCommit(function () use ($reserva, $franja, $ubicacion) {
                    $this->disponibilidad->olvidar($franja->fechaServicio, $ubicacion->id);
                    ReservaCreada::dispatch($reserva);
                });

                return $reserva->load(['mesas', 'ubicacion.seccion']);
            }

            throw new SinDisponibilidadException(
                'No hay mesas disponibles para '.$personas.' personas el '.
                $franja->fechaServicio->format('d/m/Y').' a las '.$franja->inicio->format('H:i').'.'
            );
        });
    }

    public function cancelar(Reserva $reserva): void
    {
        DB::transaction(function () use ($reserva) {
            $fecha = $reserva->fecha_servicio;
            $ubicacionId = $reserva->ubicacion_id;

            $reserva->delete();

            DB::afterCommit(fn () => $this->disponibilidad->olvidar($fecha, $ubicacionId));
        });
    }

    /**
     * Recorre las ubicaciones en orden y devuelve con cual se resuelve la reserva.
     *
     * Con OrdenEstricto gana la primera que tenga lugar. Con AjusteExactoPrimero se
     * sigue recorriendo en orden hasta encontrar una que no desperdicie asientos, y
     * solo si ninguna puede se vuelve a la primera que tenia lugar, que es la misma
     * respuesta que hubiera dado OrdenEstricto.
     *
     * El corte anticipado importa: apenas una zona ofrece un ajuste exacto se deja de
     * mirar (y de bloquear) las siguientes.
     *
     * Nota: "exacto" se evalua sobre la mejor oferta de cada zona, y dentro de una zona
     * sigue mandando el criterio de usar menos mesas. Una zona con una mesa de 6 libre
     * y dos de 2 se considera no exacta para un grupo de 4: partir al grupo en dos
     * mesas para no desperdiciar asientos seria peor experiencia que sentarlo junto.
     *
     * @return array{0: ?Ubicacion, 1: ?CombinacionDeMesas}
     */
    private function elegirUbicacion(Franja $franja, int $personas): array
    {
        $estrategia = EstrategiaAsignacion::configurada();
        $primeraConLugar = [null, null];

        foreach (Ubicacion::enOrdenDeAsignacion()->get() as $ubicacion) {
            $combinacion = $this->asignador->resolver(
                $this->mesasLibresBloqueadas($ubicacion, $franja),
                $personas
            );

            if ($combinacion === null) {
                continue; // esta zona no da: probamos la siguiente en orden
            }

            if ($estrategia === EstrategiaAsignacion::OrdenEstricto) {
                return [$ubicacion, $combinacion];
            }

            if ($combinacion->desperdicio($personas) === 0) {
                return [$ubicacion, $combinacion];
            }

            $primeraConLugar[0] ??= $ubicacion;
            $primeraConLugar[1] ??= $combinacion;
        }

        return $primeraConLugar;
    }

    /**
     * Mesas de la ubicacion libres en la franja, leidas con lock de escritura.
     *
     * La cache no participa de esta decision: sirve para pintar pantallas, no para
     * confirmar. Aca la verdad se re-verifica contra la base dentro de la transaccion.
     *
     * @return Collection<int, Mesa>
     */
    private function mesasLibresBloqueadas(Ubicacion $ubicacion, Franja $franja): Collection
    {
        // FOR UPDATE sobre las mesas de la zona: funciona de mutex por ubicacion.
        // Como el recorrido es siempre A -> B -> C -> D, todos los requests toman los
        // locks en el mismo orden y no puede haber deadlock entre dos altas simultaneas.
        $mesas = Mesa::query()
            ->where('ubicacion_id', $ubicacion->id)
            ->orderBy('numero')
            ->lockForUpdate()
            ->get();

        // Lectura bloqueante tambien aca: una lectura consistente comun podria servirse
        // del snapshot inicial de la transaccion y no ver una reserva recien commiteada.
        $ocupadas = DB::table('mesa_reserva')
            ->join('reservas', 'reservas.id', '=', 'mesa_reserva.reserva_id')
            ->where('reservas.ubicacion_id', $ubicacion->id)
            ->whereNull('reservas.deleted_at')
            ->where('reservas.starts_at', '<', $franja->fin)
            ->where('reservas.ends_at', '>', $franja->inicio)
            ->lockForUpdate()
            ->pluck('mesa_reserva.mesa_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $mesas->reject(fn (Mesa $mesa) => in_array($mesa->id, $ocupadas, true))->values();
    }
}
