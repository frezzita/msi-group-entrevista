<?php

namespace App\Services\Reservas;

use App\Models\Mesa;
use Generator;
use Illuminate\Support\Collection;

/**
 * Elige que mesas de una ubicacion recibe un grupo.
 *
 * El enunciado fija el orden entre ubicaciones ("la ubicacion la define el sistema
 * por orden") y el tope de 3 mesas, pero no dice como elegir dentro de una ubicacion.
 * El criterio implementado, en este orden:
 *
 *   1. menos mesas          una mesa sola siempre le gana a una union
 *   2. menor desperdicio    no sentar a 2 personas en la mesa de 10
 *   3. menor numero de mesa desempate estable, para que el resultado sea reproducible
 *
 * Se recorren los tamanos de combinacion de menor a mayor y se corta en el primero
 * que da solucion, asi que el criterio 1 sale gratis y no se generan combinaciones
 * de 3 mesas si con una alcanza.
 */
class AsignadorDeMesas
{
    /**
     * @param  Collection<int, Mesa>  $mesasLibres
     */
    public function resolver(Collection $mesasLibres, int $personas): ?CombinacionDeMesas
    {
        $mesas = $mesasLibres->sortBy('numero')->values()->all();
        $tope = min((int) config('reservas.max_mesas_por_reserva'), count($mesas));

        for ($cantidad = 1; $cantidad <= $tope; $cantidad++) {
            $mejor = null;

            foreach ($this->combinaciones($mesas, $cantidad) as $candidatas) {
                $combinacion = CombinacionDeMesas::de($candidatas);

                if ($combinacion->capacidadEfectiva < $personas) {
                    continue;
                }

                if ($mejor === null || $this->esMejor($combinacion, $mejor, $personas)) {
                    $mejor = $combinacion;
                }
            }

            if ($mejor !== null) {
                return $mejor;
            }
        }

        return null;
    }

    private function esMejor(CombinacionDeMesas $candidata, CombinacionDeMesas $actual, int $personas): bool
    {
        $desperdicioCandidata = $candidata->desperdicio($personas);
        $desperdicioActual = $actual->desperdicio($personas);

        if ($desperdicioCandidata !== $desperdicioActual) {
            return $desperdicioCandidata < $desperdicioActual;
        }

        return $candidata->numeros() < $actual->numeros();
    }

    /**
     * Subconjuntos de $cantidad mesas, sin repetir y respetando el orden de entrada.
     *
     * @param  list<Mesa>  $mesas
     * @return Generator<int, list<Mesa>>
     */
    private function combinaciones(array $mesas, int $cantidad, int $desde = 0): Generator
    {
        if ($cantidad === 0) {
            yield [];

            return;
        }

        $total = count($mesas);

        for ($i = $desde; $i <= $total - $cantidad; $i++) {
            foreach ($this->combinaciones($mesas, $cantidad - 1, $i + 1) as $resto) {
                yield array_merge([$mesas[$i]], $resto);
            }
        }
    }
}
