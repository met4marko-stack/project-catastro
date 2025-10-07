<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('predios', function (Blueprint $table) {
            // 1. Añadir la nueva columna, permitiendo valores nulos temporalmente.
            $table->foreignId('id_material_via')->nullable()->constrained('materiales_via');
        });


        Schema::table('predios', function (Blueprint $table) {
            // 3. Eliminar la columna de texto antigua que ya no es necesaria.
            $table->dropColumn('material_via');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('predios', function (Blueprint $table) {
            // Para revertir, hacemos el proceso inverso:
            // 1. Añadimos de nuevo la columna de texto.
            $table->string('material_via')->nullable();
        });

        Schema::table('predios', function (Blueprint $table) {
            // 3. Eliminamos la columna de ID.
            $table->dropForeign(['id_material_via']);
            $table->dropColumn('id_material_via');
        });
    }
};