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
        Schema::table('users', function (Blueprint $table) {
            // Añadimos la columna para vincular al municipio
            $table->foreignId('municipio_id')
                ->nullable() // <-- La clave para el Super Admin
                ->after('id') // O donde prefieras
                ->constrained('municipios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['municipio_id']);
        $table->dropColumn('municipio_id');
    });
    }
};
