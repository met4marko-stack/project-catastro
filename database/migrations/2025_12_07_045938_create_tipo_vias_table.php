<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_vias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // CALLE, AVENIDA, PASAJE...
            $table->timestamps();
        });

        // Insertar datos base
        DB::table('tipo_vias')->insert([
            ['nombre' => 'CALLE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'AVENIDA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'PASAJE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'CAMINO', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'CARRETERA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'PLAZA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'PARQUE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'PROLONGACION', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'OTRO', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_vias');
    }
};