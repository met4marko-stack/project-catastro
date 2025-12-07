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
        Schema::create('propietario_predio_estados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // 'Propietario Actual', 'Ex-Propietario'
            $table->timestamps();
        });

        // Insertar estados iniciales
        DB::table('propietario_predio_estados')->insert([
            ['nombre' => 'Propietario Actual', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Ex-Propietario', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propietario_predio_estados');
    }
};