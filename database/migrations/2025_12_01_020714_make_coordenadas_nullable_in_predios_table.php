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
            // Usamos SQL directo para PostgreSQL porque Doctrine/Laravel a veces falla con tipos geométricos complejos
             DB::statement('ALTER TABLE predios ALTER COLUMN coordenadas DROP NOT NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('predios', function (Blueprint $table) {
            // Volvemos a poner la restricción NOT NULL
            // Nota: Esto fallará si hay registros con coordenadas NULL al momento de revertir
             DB::statement('ALTER TABLE predios ALTER COLUMN coordenadas SET NOT NULL');
        });
    }
};