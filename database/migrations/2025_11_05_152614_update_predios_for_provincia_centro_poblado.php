<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePrediosForProvinciaCentroPoblado extends Migration
{
    public function up()
    {
        Schema::table('predios', function (Blueprint $table) {
            // 1. Añadir las nuevas columnas de clave foránea (nullable por ahora)
            $table->foreignId('provincia_id')->nullable()->after('lote')->constrained('provincias');
            $table->foreignId('centro_poblado_id')->nullable()->after('provincia_id')->constrained('centro_poblados');

            // 2. Aquí podrías correr un script para migrar datos de las columnas viejas a las nuevas
            //    (Se omite por simplicidad, se asume que se llenarán de nuevo)

            // 3. Eliminar las columnas antiguas
            $table->dropColumn('provincia');
            $table->dropColumn('centro_poblado');
        });
    }

    public function down()
    {
        Schema::table('predios', function (Blueprint $table) {
            // 1. Añadir de nuevo las columnas de texto
            $table->string('provincia')->nullable()->after('lote');
            $table->string('centro_poblado')->nullable()->after('provincia');

            // 2. Eliminar las claves foráneas y las columnas
            $table->dropForeign(['provincia_id']);
            $table->dropForeign(['centro_poblado_id']);
            $table->dropColumn('provincia_id');
            $table->dropColumn('centro_poblado_id');
        });
    }
}