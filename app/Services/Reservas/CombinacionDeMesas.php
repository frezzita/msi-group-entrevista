<?php

namespace App\Services\Reservas;

use App\Models\Mesa;
use Illuminate\Support\Collection;

/** Una mesa o un conjunto de mesas unidas que puede recibir a un grupo. */
final class CombinacionDeMesas
{
    /** @param  Collection<int, Mesa>  $mesas */
    private function __construct(
        public readonly Collection $mesas,
        public readonly int $capacidadEfectiva,
    ) {}

    /**
     * Al unir N mesas se pierden asientos en cada juntura. El enunciado no define
     * esa perdida, asi que el default configurado es 0 (suma simple) y el calculo
     * queda listo para el valor realista sin tocar codigo.
     *
     * @param  iterable<Mesa>  $mesas
     */
    public static function de(iterable $mesas): self
    {
        $mesas = collect($mesas)->sortBy('numero')->values();
        $junturas = max(0, $mesas->count() - 1);
        $perdida = $junturas * (int) config('reservas.asientos_perdidos_por_union');

        return new self($mesas, max(0, $mesas->sum('capacidad') - $perdida));
    }

    /** @return list<int> */
    public function ids(): array
    {
        return $this->mesas->pluck('id')->all();
    }

    /** @return list<int> */
    public function numeros(): array
    {
        return $this->mesas->pluck('numero')->all();
    }

    public function cantidad(): int
    {
        return $this->mesas->count();
    }

    /** Asientos que quedan sin usar. Menos desperdicio es mejor asignacion. */
    public function desperdicio(int $personas): int
    {
        return $this->capacidadEfectiva - $personas;
    }
}
