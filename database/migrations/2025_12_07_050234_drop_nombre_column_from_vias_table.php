<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vias', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('vias', function (Blueprint $table) {
            $table->string('nombre')->nullable(); // No podemos recuperar los datos fácilmente en el down
        });
    }
};