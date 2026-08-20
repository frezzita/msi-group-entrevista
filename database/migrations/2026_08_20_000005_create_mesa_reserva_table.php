<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivote N:N entre reservas y mesas: una reserva puede ocupar hasta 3 mesas unidas.
 * Se llama mesa_reserva (orden alfabetico singular) para que belongsToMany lo resuelva
 * por convencion sin configurar el nombre de la tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesa_reserva', function (Blueprint $table) {
            $table->foreignId('mesa_id')->constrained('mesas')->restrictOnDelete();
            $table->foreignId('reserva_id')->constrained('reservas')->cascadeOnDelete();

            $table->primary(['mesa_id', 'reserva_id']);
            $table->index('reserva_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesa_reserva');
    }
};
