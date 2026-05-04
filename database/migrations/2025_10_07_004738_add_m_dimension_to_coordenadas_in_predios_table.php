<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cambia el tipo de la columna para que acepte las dimensiones Z y M
        DB::statement('ALTER TABLE predios ALTER COLUMN coordenadas TYPE geometry(MultiPolygonZM, 32719)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revierte el cambio a una geometría con solo dimensión Z
        DB::statement('ALTER TABLE predios ALTER COLUMN coordenadas TYPE geometry(MultiPolygonZ, 32719)');
    }
};