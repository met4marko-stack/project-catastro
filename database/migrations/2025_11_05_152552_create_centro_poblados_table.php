<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCentroPobladosTable extends Migration
{
    public function up()
    {
        Schema::create('centro_poblados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            // Aquí podrías enlazarlo a un municipio o provincia si quisieras
            // $table->foreignId('municipio_id')->nullable()->constrained('municipios');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('centro_poblados');
    }
}