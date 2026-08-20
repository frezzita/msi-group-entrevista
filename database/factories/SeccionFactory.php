<?php

namespace Database\Factories;

use App\Models\Seccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Seccion> */
class SeccionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Seccion '.fake()->unique()->numerify('###'),
        ];
    }
}
