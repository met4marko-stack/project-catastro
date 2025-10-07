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
            // Añade la nueva columna para la clave foránea, puede ser nula
            $table->unsignedBigInteger('via_id')->nullable()->after('municipio_id');

            // Define la restricción de la clave foránea
            $table->foreign('via_id')->references('id')->on('vias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('predios', function (Blueprint $table) {
            // Elimina primero la restricción de la clave foránea
            $table->dropForeign(['via_id']);
            
            // Luego, elimina la columna
            $table->dropColumn('via_id');
        });
    }
};