<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_colindantes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); 
            $table->timestamps();
        });

        // Insertar datos base
        DB::table('tipo_colindantes')->insert([
            ['nombre' => 'LOTE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'VIA', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'AREA VERDE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'EQUIPAMIENTO', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'RIO', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'OTRO', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_colindantes');
    }
};