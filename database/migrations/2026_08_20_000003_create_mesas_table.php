<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ubicacion_id')->constrained('ubicaciones')->restrictOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->unsignedSmallInteger('capacidad');
            $table->softDeletes();
            $table->timestamps();

            // deleted_at forma parte del unique a proposito: MySQL admite multiples
            // NULL en un indice unico, asi que esto significa "unico entre las mesas
            // vivas" y permite dar de baja la mesa 5 de la zona A y volver a crearla.
            $table->unique(['ubicacion_id', 'numero', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
