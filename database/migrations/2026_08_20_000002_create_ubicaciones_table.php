<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 20)->unique();

            // El enunciado dice que la ubicacion la asigna el sistema "por orden".
            // Esta columna materializa ese orden (A=1, B=2, C=3, D=4) en vez de
            // depender del orden alfabetico o del id.
            $table->unsignedTinyInteger('orden')->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};
