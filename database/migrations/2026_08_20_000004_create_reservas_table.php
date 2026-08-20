<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('ubicacion_id')->constrained('ubicaciones')->restrictOnDelete();

            // fecha_servicio es el "dia de negocio", no la fecha de calendario de
            // starts_at: una reserva del sabado 23:30 termina el domingo 01:30 pero
            // pertenece al servicio del sabado. Es lo que consulta el punto 4.
            $table->date('fecha_servicio');

            // Datetimes completos en vez de date + time: el solapamiento queda en una
            // comparacion directa y el cruce de medianoche del sabado deja de ser un
            // caso especial en cada query.
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->unsignedSmallInteger('cantidad_personas');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['fecha_servicio', 'ubicacion_id']); // punto 4 + busqueda de disponibilidad
            $table->index(['starts_at', 'ends_at']);           // pantalla de estado (ocupacion actual)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
