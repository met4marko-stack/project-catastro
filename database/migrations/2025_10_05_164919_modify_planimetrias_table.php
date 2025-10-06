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
        Schema::table('planimetrias', function (Blueprint $table) {
            // 1. Cambiar el tipo de dato de la columna 'codigo'
            $table->string('codigo')->change(); // Por defecto, string crea un VARCHAR(255)

            // 2. Añadir la nueva columna 'centro_poblado'
            // Se añade después de 'municipio_id' para mantener un orden lógico
            $table->string('centro_poblado')->nullable()->after('municipio_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planimetrias', function (Blueprint $table) {
            // Revierte los cambios en orden inverso
            $table->dropColumn('centro_poblado');
            $table->integer('codigo')->change();
        });
    }
};