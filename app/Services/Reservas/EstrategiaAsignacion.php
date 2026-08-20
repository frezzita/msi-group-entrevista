<?php

namespace App\Services\Reservas;

/**
 * Como se elige la ubicacion cuando mas de una podria recibir al grupo.
 *
 * El enunciado dice que la ubicacion la define el sistema "por orden", pero no aclara
 * que hacer cuando la primera zona con lugar solo puede ofrecer una mesa mas grande de
 * lo necesario y una zona posterior tiene uno exacto.
 */
enum EstrategiaAsignacion: string
{
    /**
     * Lectura literal del enunciado: gana la primera ubicacion con lugar, sin mirar
     * las siguientes. Dentro de esa ubicacion igual se aplica el mejor ajuste.
     *
     * Concentra a los comensales en las primeras zonas, que es lo que suele querer un
     * local real: cada zona abierta necesita personal propio.
     */
    case OrdenEstricto = 'orden_estricto';

    /**
     * Prioriza no desperdiciar asientos: recorre las ubicaciones en orden buscando una
     * que pueda recibir al grupo sin sobrar lugares, y solo si ninguna puede vuelve a
     * la primera que tenia lugar.
     *
     * Evita que un grupo de 2 queme la mesa de 4 y empuje al grupo de 4 siguiente a una
     * mesa todavia mas grande. A cambio puede dispersar grupos chicos entre zonas.
     */
    case AjusteExactoPrimero = 'ajuste_exacto_primero';

    public static function configurada(): self
    {
        return self::from(config('reservas.estrategia_asignacion'));
    }
}
