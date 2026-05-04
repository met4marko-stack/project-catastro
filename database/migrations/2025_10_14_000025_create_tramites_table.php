<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tramites', function (Blueprint $table) {
            $table->id();

            // Llaves foráneas principales
            $table->foreignId('predio_id')->constrained('predios')->cascadeOnDelete();
            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnDelete();
            $table->foreignId('usuario_id')->comment('Usuario del sistema que registra el trámite')->constrained('users');
            $table->foreignId('solicitante_id')->comment('Persona (propietario o apoderado) que inicia el trámite')->constrained('personas');
            $table->foreignId('tramite_tipo_id')->constrained('tramite_tipos');
            $table->foreignId('estado_id')->constrained('tramite_estados');

            // Campos de información del trámite
            $table->string('hoja_ruta')->nullable();
            $table->date('fecha_ingreso');
            $table->date('fecha_inspeccion')->nullable();
            $table->date('fecha_conclusion')->nullable();
            $table->text('observaciones')->nullable();

            // Campo para la lógica de vencimiento
            $table->date('fecha_paralizado')->nullable()->comment('Fecha en que el trámite pasa a "Paralizado" para el contador de 10 días.');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tramites');
    }
};