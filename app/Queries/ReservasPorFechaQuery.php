<?php

namespace App\Queries;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Punto 4: reservas de una fecha, por ubicacion y seccion, con las mesas de cada una,
 * en una sola consulta.
 *
 * Se resuelve con el query builder y no con Eloquent: `Reserva::with('mesas')` son dos
 * consultas como minimo (una por las reservas y otra por el pivote), y cargar las mesas
 * en un accessor serian N+1. Con el join contra el pivote mas GROUP_CONCAT cada reserva
 * vuelve en una unica fila con sus mesas ya concatenadas, en un solo viaje a la base.
 *
 * Indice que la sostiene: reservas(fecha_servicio, ubicacion_id).
 */
class ReservasPorFechaQuery
{
    /**
     * @return Collection<int, object>
     */
    public function paraFecha(CarbonInterface|string $fecha): Collection
    {
        $fecha = $fecha instanceof CarbonInterface ? $fecha->toDateString() : $fecha;

        return DB::table('reservas as r')
            ->join('ubicaciones as u', 'u.id', '=', 'r.ubicacion_id')
            ->join('secciones as s', 's.id', '=', 'u.seccion_id')
            ->join('mesa_reserva as mr', 'mr.reserva_id', '=', 'r.id')
            ->join('mesas as m', 'm.id', '=', 'mr.mesa_id')
            ->where('r.fecha_servicio', $fecha)
            ->whereNull('r.deleted_at')
            ->groupBy('r.id', 'r.starts_at', 'r.ends_at', 'r.cantidad_personas', 's.nombre', 'u.nombre', 'u.orden')
            ->orderBy('u.orden')
            ->orderBy('r.starts_at')
            ->get([
                'r.id as reserva_id',
                'r.starts_at',
                'r.ends_at',
                'r.cantidad_personas',
                's.nombre as seccion',
                'u.nombre as ubicacion',
                DB::raw("GROUP_CONCAT(m.numero ORDER BY m.numero SEPARATOR ', ') as mesas"),
            ]);
    }

    /**
     * Mismo resultado agrupado para la vista. El agrupado se hace en memoria sobre las
     * filas que ya vinieron: no agrega consultas.
     *
     * @return Collection<string, Collection<int, object>>
     */
    public function agrupadasPorUbicacion(CarbonInterface|string $fecha): Collection
    {
        return $this->paraFecha($fecha)->groupBy(fn (object $r) => $r->seccion.' / '.$r->ubicacion);
    }

    /** SQL final, para documentarlo y para el EXPLAIN del README. */
    public function sql(): string
    {
        return DB::table('reservas as r')
            ->join('ubicaciones as u', 'u.id', '=', 'r.ubicacion_id')
            ->join('secciones as s', 's.id', '=', 'u.seccion_id')
            ->join('mesa_reserva as mr', 'mr.reserva_id', '=', 'r.id')
            ->join('mesas as m', 'm.id', '=', 'mr.mesa_id')
            ->where('r.fecha_servicio', '?')
            ->whereNull('r.deleted_at')
            ->groupBy('r.id', 'r.starts_at', 'r.ends_at', 'r.cantidad_personas', 's.nombre', 'u.nombre', 'u.orden')
            ->orderBy('u.orden')
            ->orderBy('r.starts_at')
            ->toSql();
    }
}
