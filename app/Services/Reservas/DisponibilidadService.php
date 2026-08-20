<?php

namespace App\Services\Reservas;

use App\Models\Mesa;
use App\Models\Ubicacion;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cache en memoria de la disponibilidad por ubicacion (punto 3 del enunciado).
 *
 * Se cachean los intervalos ocupados del dia completo para una ubicacion, no el
 * resultado por franja horaria. La razon: si la hora de inicio es libre, las franjas
 * son infinitas, no existe "la entrada de cache de esa franja" para invalidar y hace
 * falta borrado por patron o tags. Con una entrada por (dia, ubicacion) la clave es
 * literalmente la disponibilidad de una ubicacion, la cardinalidad es dias x 4 y la
 * invalidacion es un forget de una clave exacta que funciona con cualquier cache store.
 *
 * Esta clase resuelve LECTURAS. La confirmacion de una reserva no se apoya en ella:
 * ReservaService re-verifica contra la base dentro de la transaccion.
 */
class DisponibilidadService
{
    public static function clave(string $fechaServicio, int $ubicacionId): string
    {
        return "disponibilidad:{$fechaServicio}:{$ubicacionId}";
    }

    /**
     * Intervalos ocupados de una ubicacion en un dia de servicio.
     *
     * @return Collection<int, array{mesa_id: int, starts_at: string, ends_at: string}>
     */
    public function ocupacion(CarbonInterface $fechaServicio, int $ubicacionId): Collection
    {
        $fecha = $fechaServicio->toDateString();

        return collect(Cache::remember(
            self::clave($fecha, $ubicacionId),
            now()->addMinutes((int) config('reservas.cache_ttl_minutos')),
            fn () => $this->consultarOcupacion($fecha, $ubicacionId),
        ));
    }

    /** @return list<int> ids de mesas ocupadas en esa franja */
    public function mesasOcupadas(Franja $franja, int $ubicacionId): array
    {
        $inicio = $franja->inicio->format('Y-m-d H:i:s');
        $fin = $franja->fin->format('Y-m-d H:i:s');

        return $this->ocupacion($franja->fechaServicio, $ubicacionId)
            // Mismas desigualdades estrictas que el scope de Reserva: los intervalos
            // que apenas se tocan en el borde no se pisan.
            ->filter(fn (array $i) => $i['starts_at'] < $fin && $i['ends_at'] > $inicio)
            ->pluck('mesa_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Mesas de una ubicacion sin reserva en esa franja.
     *
     * Las mesas se leen de la base y no de la cache a proposito: son un dato estable
     * y cachearlas obligaria a invalidar todas las fechas cada vez que el ABM toca
     * una mesa. Lo volatil, que son las reservas, es lo que se cachea.
     *
     * @return Collection<int, Mesa>
     */
    public function mesasLibres(Ubicacion $ubicacion, Franja $franja): Collection
    {
        $ocupadas = $this->mesasOcupadas($franja, $ubicacion->id);

        return $ubicacion->mesas()
            ->orderBy('numero')
            ->get()
            ->reject(fn (Mesa $mesa) => in_array($mesa->id, $ocupadas, true))
            ->values();
    }

    public function olvidar(CarbonInterface|string $fechaServicio, int $ubicacionId): void
    {
        $fecha = $fechaServicio instanceof CarbonInterface ? $fechaServicio->toDateString() : $fechaServicio;

        Cache::forget(self::clave($fecha, $ubicacionId));
    }

    /**
     * @return list<array{mesa_id: int, starts_at: string, ends_at: string}>
     */
    private function consultarOcupacion(string $fecha, int $ubicacionId): array
    {
        // Filtrar por fecha_servicio alcanza porque las ventanas de dias consecutivos
        // nunca se superponen: el servicio del sabado termina a las 02:00 del domingo
        // y el del domingo empieza a las 12:00.
        return DB::table('reservas')
            ->join('mesa_reserva', 'mesa_reserva.reserva_id', '=', 'reservas.id')
            ->where('reservas.fecha_servicio', $fecha)
            ->where('reservas.ubicacion_id', $ubicacionId)
            ->whereNull('reservas.deleted_at')
            ->get(['mesa_reserva.mesa_id', 'reservas.starts_at', 'reservas.ends_at'])
            ->map(fn ($fila) => [
                'mesa_id' => (int) $fila->mesa_id,
                'starts_at' => (string) $fila->starts_at,
                'ends_at' => (string) $fila->ends_at,
            ])
            ->all();
    }
}
