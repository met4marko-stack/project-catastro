<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramite_tipos', function (Blueprint $table) {
            $table->decimal('costo', 10, 2)->default(0.00);
        });

        // Precios propuestos por tipo de trámite
        $precios = [
            'Aprobación de Plano de Lote Georeferenciado' => 150.00,
            'Aprobación de Plano de División y Partición' => 200.00,
            'Aprobación de Plano de Fusión o Anexión' => 180.00,
            'Aprobación de Planos de Fraccionamiento de Propiedad Horizontal' => 350.00,
            'Autorización y Emisión de Certificado de Línea y Nivel Municipal' => 100.00,
            'Aprobación de Planos de Construcción' => 300.00,
            'Aprobación de Plano de Ampliación y/o Remodelación de Construcción' => 250.00,
            'Certificaciones Técnicas Varias' => 80.00,
            'Legalización de Trámites Técnicos Administrativos' => 50.00,
            'Permiso de Trabajos Menores, Demoliciones' => 120.00,
        ];

        foreach ($precios as $nombre => $costo) {
            DB::table('tramite_tipos')
                ->where('nombre', $nombre)
                ->update(['costo' => $costo]);
        }
    }

    public function down(): void
    {
        Schema::table('tramite_tipos', function (Blueprint $table) {
            $table->dropColumn('costo');
        });
    }
};
