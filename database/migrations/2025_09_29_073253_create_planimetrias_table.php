<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planimetrias', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo');
            $table->date('fecha_aprobacion')->nullable();
            $table->string('documento_aprobacion')->nullable();
            
            $table->foreignId('municipio_id')->constrained('municipios')->onDelete('cascade');
            
            $table->geometry('limite_geografico', subtype: 'polygon', srid: 4326);
            
            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planimetrias');
    }
};