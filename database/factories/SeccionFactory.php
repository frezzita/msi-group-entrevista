<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Seccion> */
class SeccionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Seccion '.fake()->unique()->numerify('###'),
        ];
    }
}
