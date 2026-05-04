<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar la vista dependiente
        DB::statement("DROP VIEW IF EXISTS v_predios_map");

        // 2. Eliminar la columna existente
        Schema::table('predios', function (Blueprint $table) {
            $table->dropColumn('codigo_catastral');
        });

        // 3. Crear la nueva columna generada con lógica de padding condicional
        // Si la longitud es 1, agregamos '0' al inicio. Si no, dejamos tal cual.
        DB::statement("
            ALTER TABLE predios 
            ADD COLUMN codigo_catastral VARCHAR(255) 
            GENERATED ALWAYS AS (
                '01' || 
                (CASE WHEN length(manzano) = 1 THEN '0' || manzano ELSE COALESCE(manzano, '') END) || 
                (CASE WHEN length(lote) = 1 THEN '0' || lote ELSE COALESCE(lote, '') END) || 
                (CASE WHEN numero_unidad IS NOT NULL AND numero_unidad != '' THEN '-' || numero_unidad ELSE '' END)
            ) STORED
        ");
        
        // 4. Recrear la vista
        DB::statement("CREATE VIEW v_predios_map AS SELECT * FROM predios");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_predios_map");
        
        Schema::table('predios', function (Blueprint $table) {
            $table->dropColumn('codigo_catastral');
        });

        DB::statement("
            ALTER TABLE predios 
            ADD COLUMN codigo_catastral VARCHAR(255) 
            GENERATED ALWAYS AS ('01' || COALESCE(manzano, '') || COALESCE(lote, '') || CASE WHEN numero_unidad IS NOT NULL AND numero_unidad != '' THEN '-' || numero_unidad ELSE '' END) STORED
        ");

        DB::statement("CREATE VIEW v_predios_map AS SELECT * FROM predios");
    }
};