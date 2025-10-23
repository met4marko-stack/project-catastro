<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tramite_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tramite_id')->constrained('tramites')->cascadeOnDelete();
            $table->foreignId('requisito_id')->constrained('requisitos')->cascadeOnDelete();
            $table->foreignId('estado_id')->constrained('documento_estados');
            
            $table->text('ruta_archivo');
            $table->string('nombre_original');
            $table->text('observaciones')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tramite_documentos');
    }
};