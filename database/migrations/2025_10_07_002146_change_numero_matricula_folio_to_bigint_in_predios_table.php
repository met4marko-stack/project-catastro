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
        Schema::table('predios', function (Blueprint $table) {
            // Cambia el tipo de la columna a string (varchar) de 255 caracteres
            $table->string('numero_matricula_folio', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('predios', function (Blueprint $table) {
            // Revierte el cambio a bigint (o integer si prefieres)
            $table->bigInteger('numero_matricula_folio')->change();
        });
    }
};