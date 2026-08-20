<?php

namespace Database\Factories;

use App\Models\Seccion;
use App\Models\Ubicacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ubicacion> */
class UbicacionFactory extends Factory
{
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 10000);

        return [
            'seccion_id' => Seccion::factory(),
            'nombre' => 'Z'.$n,
            'orden' => $n,
        ];
    }
}
