<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            
            // Relación con la tabla municipios
            $table->foreignId('municipio_id')->constrained('municipios')->onDelete('cascade');
            
            $table->timestamps();

            // Restricción para evitar nombres de vías duplicados dentro del mismo municipio
            $table->unique(['nombre', 'municipio_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vias');
    }
};
