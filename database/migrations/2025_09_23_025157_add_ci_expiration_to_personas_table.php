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
        Schema::table('personas', function (Blueprint $table) {
            // Se añade después de la columna 'expedido' para mantener el orden
            $table->date('ci_fecha_caducidad')->nullable()->after('expedido');
            $table->boolean('ci_es_indefinido')->default(false)->after('ci_fecha_caducidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn('ci_fecha_caducidad');
            $table->dropColumn('ci_es_indefinido');
        });
    }
};