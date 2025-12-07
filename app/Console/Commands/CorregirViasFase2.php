<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PredioColindancia;
use App\Models\TipoColindante;
use App\Models\Via;
use Illuminate\Support\Facades\DB;

class CorregirViasFase2 extends Command
{
    protected $signature = 'catastro:corregir-vias-fase2';
    protected $description = 'Correcciones específicas y creación de vías faltantes';

    public function handle()
    {
        $this->info('Iniciando Fase 2 de corrección de vías...');

        $tipoVia = TipoColindante::where('nombre', 'VIA')->first();
        
        // Mapa de correcciones directas (Typo -> Nombre Correcto en BD Vias)
        $correcciones = [
            'AV.PANAMERICANA' => 'AVENIDA PANAMERICANA',
            'CALLE ANTOAGASTA' => 'CALLE ANTOFAGASTA',
            'CALLE CORO CORO' => 'CALLE COROCORO',
            'CALLE LITORIAL' => 'CALLE LITORAL',
            'CALLE S/N.' => 'CALLE SIN NOMBRE',
        ];

        // Mapa de Vías Nuevas a Crear (Nombre encontrado -> Nombre Oficial Nuevo)
        $nuevasVias = [
            'AV. LITORAL' => 'AVENIDA LITORAL',
            'AVENIDA LITORAL' => 'AVENIDA LITORAL',
            'CALLE CHIJIMARCA' => 'CALLE CHIJIMARCA',
            'CALLE CHIJIMARKA' => 'CALLE CHIJIMARCA', 
            'CALLE TUPAC AMARU' => 'CALLE TUPAC AMARU',
        ];

        DB::beginTransaction();
        try {
            // Asumimos municipio_id = 12 basado en los datos existentes
            $municipioId = 12; 

            // 1. Aplicar Correcciones de Typos (Vincular a vías existentes)
            foreach ($correcciones as $malNombre => $buenNombre) {
                $via = Via::where('nombre', $buenNombre)->first();
                if ($via) {
                    $afectados = PredioColindancia::where('tipo_colindante_id', $tipoVia->id)
                        ->whereNull('via_id')
                        ->where('nombre_o_numero', $malNombre)
                        ->update(['via_id' => $via->id, 'nombre_o_numero' => null]);
                    
                    if ($afectados > 0) {
                        $this->info("Corregido: {$malNombre} -> {$buenNombre} ({$afectados} registros)");
                    }
                } else {
                    $this->warn("Advertencia: La vía destino '{$buenNombre}' no existe en la BD.");
                }
            }

            // 2. Crear Vías Nuevas y Vincular
            foreach ($nuevasVias as $nombreEncontrado => $nombreOficial) {
                $via = Via::firstOrCreate(
                    ['nombre' => $nombreOficial, 'municipio_id' => $municipioId],
                    ['created_at' => now(), 'updated_at' => now()]
                );

                $afectados = PredioColindancia::where('tipo_colindante_id', $tipoVia->id)
                        ->whereNull('via_id')
                        ->where('nombre_o_numero', $nombreEncontrado)
                        ->update(['via_id' => $via->id, 'nombre_o_numero' => null]);

                if ($afectados > 0) {
                    $this->info("Creada/Vinculada: {$nombreEncontrado} -> {$nombreOficial} (ID: {$via->id}) ({$afectados} registros)");
                }
            }

            DB::commit();
            $this->info('Fase 2 completada con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
        }
    }
}