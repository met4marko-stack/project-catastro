<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propietarios_predios', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('propietario_id')->constrained('propietarios')->onDelete('cascade');
            $table->foreignId('predio_id')->constrained('predios')->onDelete('cascade');
            
            $table->string('estado')->comment('Ej: Propietario Actual, Ex-propietario');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            
            $table->timestamps();

            $table->unique(['propietario_id', 'predio_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propietarios_predios');
    }
};