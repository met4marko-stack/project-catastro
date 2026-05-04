<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predio_colindancias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('predio_id')->constrained('predios')->onDelete('cascade');
            $table->foreignId('orientacion_id')->constrained('orientaciones');
            $table->foreignId('tipo_colindante_id')->constrained('tipo_colindantes');
            
            // Relación opcional con la tabla de vías existente
            $table->foreignId('via_id')->nullable()->constrained('vias')->nullOnDelete();
            
            // Para guardar el número de lote ("19") o el nombre si no es una vía registrada
            $table->string('nombre_o_numero')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predio_colindancias');
    }
};