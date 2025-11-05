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
        Schema::table('tramite_documentos', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->after('estado_id')
                ->nullable() // <-- Permite que sea nulo
                ->constrained('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tramite_documentos', function (Blueprint $table) {
            // Elimina la llave foránea primero
            $table->dropForeign(['user_id']);
            // Luego elimina la columna
            $table->dropColumn('user_id');
        });
    }
};
