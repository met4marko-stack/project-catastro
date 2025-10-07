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
        // Cambia el tipo de la columna para que acepte una dimensión Z
        DB::statement('ALTER TABLE predios ALTER COLUMN coordenadas TYPE geometry(MultiPolygonZ, 32719)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revierte el cambio a una geometría sin dimensión Z
        DB::statement('ALTER TABLE predios ALTER COLUMN coordenadas TYPE geometry(MultiPolygon, 32719)');
    }
};