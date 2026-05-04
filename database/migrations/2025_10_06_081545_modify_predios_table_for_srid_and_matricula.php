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
            // 1. Añade la nueva columna para el número de matrícula o folio
            $table->integer('numero_matricula_folio')->nullable()->after('codigo_catastral');

            // 2. Cambia el SRID de la columna 'coordenadas' a 32719
            $table->geometry('coordenadas', subtype: 'polygon', srid: 32719)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('predios', function (Blueprint $table) {
            // Revierte los cambios en orden inverso
            $table->geometry('coordenadas', subtype: 'polygon', srid: 4326)->change();
            $table->dropColumn('numero_matricula_folio');
        });
    }
};