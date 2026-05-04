<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propietarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->onDelete('cascade');
            $table->foreignId('municipio_id')->constrained('municipios')->onDelete('cascade');
            $table->boolean('estado')->default(true); // true = Activo, false = Inactivo
            $table->timestamps();

            // Evita que la misma persona se registre dos veces como propietario en el mismo municipio
            $table->unique(['persona_id', 'municipio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propietarios');
    }
};