<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB; // <-- Asegúrate de importar la clase DB

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Usamos DB::statement para ejecutar un comando SQL específico de PostgreSQL
        DB::statement('ALTER TABLE predios ALTER COLUMN coordenadas TYPE geometry(MultiPolygon, 32719)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Lógica para revertir el cambio
        DB::statement('ALTER TABLE predios ALTER COLUMN coordenadas TYPE geometry(Polygon, 32719)');
    }
};