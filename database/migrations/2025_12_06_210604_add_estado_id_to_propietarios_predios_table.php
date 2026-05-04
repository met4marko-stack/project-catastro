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
        // 1. Agregar la columna nullable
        Schema::table('propietarios_predios', function (Blueprint $table) {
            $table->foreignId('estado_id')->nullable()->after('predio_id')->constrained('propietario_predio_estados');
        });

        // 2. Migrar los datos existentes
        // Asumimos que todos los registros actuales son 'Propietario Actual'
        $propietarioActualId = DB::table('propietario_predio_estados')->where('nombre', 'Propietario Actual')->value('id');
        
        if ($propietarioActualId) {
            DB::table('propietarios_predios')->update(['estado_id' => $propietarioActualId]);
        }

        // 3. Hacer la columna no nullable y Eliminar la columna vieja
        Schema::table('propietarios_predios', function (Blueprint $table) {
            $table->unsignedBigInteger('estado_id')->nullable(false)->change();
            
            // Eliminamos el índice único compuesto antiguo
            // El nombre por defecto suele ser table_col1_col2_col3_unique
            $table->dropUnique('propietarios_predios_propietario_id_predio_id_estado_unique');
            
            $table->dropColumn('estado');
            
            // Creamos el nuevo índice único
            $table->unique(['propietario_id', 'predio_id', 'estado_id'], 'pp_unique_estado_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propietarios_predios', function (Blueprint $table) {
             $table->string('estado')->nullable()->comment('Ej: Propietario Actual, Ex-propietario');
        });

        // Revertir datos (best effort)
        DB::table('propietarios_predios')
            ->join('propietario_predio_estados', 'propietarios_predios.estado_id', '=', 'propietario_predio_estados.id')
            ->update(['propietarios_predios.estado' => DB::raw('propietario_predio_estados.nombre')]);

        Schema::table('propietarios_predios', function (Blueprint $table) {
            $table->dropForeign(['estado_id']);
            $table->dropUnique('pp_unique_estado_id');
            $table->dropColumn('estado_id');
            
            // No podemos garantizar que se pueda recrear el unique si hay duplicados introducidos, pero lo intentamos
            $table->unique(['propietario_id', 'predio_id', 'estado'], 'propietarios_predios_propietario_id_predio_id_estado_unique');
        });
    }
};