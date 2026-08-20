<?php

namespace Database\Factories;

use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Reserva> */
class ReservaFactory extends Factory
{
    public function definition(): array
    {
        $inicio = now()->addDay()->setTime(20, 0);

        return [
            'user_id' => User::factory(),
            'ubicacion_id' => Ubicacion::factory(),
            'fecha_servicio' => $inicio->toDateString(),
            'starts_at' => $inicio,
            'ends_at' => $inicio->copy()->addMinutes(config('reservas.duracion_minutos')),
            'cantidad_personas' => fake()->numberBetween(1, 6),
        ];
    }

    /** Fija el intervalo y deriva la fecha de servicio del inicio. */
    public function entre(\DateTimeInterface $inicio, ?\DateTimeInterface $fin = null): static
    {
        $inicio = \Illuminate\Support\Carbon::instance($inicio);

        return $this->state(fn () => [
            'fecha_servicio' => $inicio->toDateString(),
            'starts_at' => $inicio,
            'ends_at' => $fin ?? $inicio->copy()->addMinutes(config('reservas.duracion_minutos')),
        ]);
    }
}
