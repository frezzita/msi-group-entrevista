<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El enunciado nombra "ubicacion y seccion" en el punto 4, pero solo define
 * Ubicacion (A-D) como campo de la mesa. Como la mesa no tiene campo seccion,
 * seccion no puede ser una subdivision dentro de la ubicacion: se modela como
 * el nivel que las agrupa. Ver README, seccion de supuestos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secciones');
    }
};
