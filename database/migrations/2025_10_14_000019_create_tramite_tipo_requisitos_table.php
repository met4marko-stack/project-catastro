<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tramite_tipo_requisitos', function (Blueprint $table) {
            $table->foreignId('tramite_tipo_id')->constrained('tramite_tipos')->cascadeOnDelete();
            $table->foreignId('requisito_id')->constrained('requisitos')->cascadeOnDelete();
            
            // Clave primaria compuesta para asegurar que no haya duplicados
            $table->primary(['tramite_tipo_id', 'requisito_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tramite_tipo_requisitos');
    }
};