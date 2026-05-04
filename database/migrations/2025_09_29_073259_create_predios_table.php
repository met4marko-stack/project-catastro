<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predios', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('inmueble_padre_id')->nullable()->constrained('predios')->onDelete('set null');
            $table->boolean('propiedad_horizontal')->default(false);
            $table->string('numero_unidad', 20)->nullable();
            
            $table->string('codigo_catastral')->unique();
            $table->string('numero_plano', 50)->nullable();
            $table->string('manzano', 20)->nullable();
            $table->string('lote', 20)->nullable();
            $table->string('provincia')->nullable();
            $table->string('centro_poblado')->nullable();
            $table->string('zona')->nullable();

            $table->double('sup_levantamiento')->nullable();
            $table->double('sup_testimonio')->nullable();
            $table->double('sup_construida')->nullable();
            $table->double('sup_afectada')->nullable();
            $table->double('sup_util')->nullable();

            $table->geometry('coordenadas', subtype: 'polygon', srid: 4326);
            
            $table->double('frente_principal')->nullable();

            $table->boolean('agua_potable')->default(false);
            $table->boolean('energia_electrica')->default(false);
            $table->boolean('alcantarillado')->default(false);
            $table->boolean('alumbrado_publico')->default(false);
            $table->boolean('gas_domiciliario')->default(false);
            
            $table->string('material_via')->nullable();
            $table->boolean('forma_lote')->nullable();

            $table->text('fotografia_uno')->nullable();
            $table->text('fotografia_dos')->nullable();
            $table->text('fotografia_tres')->nullable();
            $table->text('fotografia_cuatro')->nullable();
            $table->text('fotografia_cinco')->nullable();

            $table->string('colindante_norte')->nullable();
            $table->string('colindante_sur')->nullable();
            $table->string('colindante_este')->nullable();
            $table->string('colindante_oeste')->nullable();

            $table->foreignId('planimetria_id')->constrained('planimetrias')->onDelete('cascade');
            $table->foreignId('municipio_id')->constrained('municipios')->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predios');
    }
};