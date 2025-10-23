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
    Schema::table('tramites', function (Blueprint $table) {
        $table->string('codigo_acceso', 8) 
              ->unique()
              ->nullable()
              ->after('hoja_ruta')
              ->comment('Código secreto para la consulta pública del solicitante.');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            //
        });
    }
};
