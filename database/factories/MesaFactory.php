<?php

namespace Database\Factories;

use App\Models\Mesa;
use App\Models\Ubicacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Mesa> */
class MesaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ubicacion_id' => Ubicacion::factory(),
            'numero' => fake()->unique()->numberBetween(1, 10000),
            'capacidad' => fake()->randomElement([2, 4, 6, 8]),
        ];
    }

    public function capacidad(int $capacidad): static
    {
        return $this->state(fn () => ['capacidad' => $capacidad]);
    }
}
