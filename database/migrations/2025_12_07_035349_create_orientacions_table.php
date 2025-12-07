<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orientaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Insertar datos base
        DB::table('orientaciones')->insert([
            ['nombre' => 'NORTE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'SUR', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'ESTE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'OESTE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'NORESTE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'NOROESTE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'SURESTE', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'SUROESTE', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('orientaciones');
    }
};