<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Predio;
use App\Models\Orientacion;
use App\Models\TipoColindante;
use App\Models\Via;
use App\Models\PredioColindancia;
use Illuminate\Support\Facades\DB;

class MigrarColindancias extends Command
{
    protected $signature = 'catastro:migrar-colindancias';
    protected $description = 'Normaliza las columnas de colindantes a la nueva tabla pivote';

    public function handle()
    {
        $this->info('Iniciando migración de colindancias...');

        // Cargar catálogos en memoria para rapidez
        $orientaciones = Orientacion::pluck('id', 'nombre'); 
        $tipos = TipoColindante::pluck('id', 'nombre');     
        
        // Cargar todas las vías.
        $viasDb = Via::all(); 

        // Procesar todos los predios
        $predios = Predio::withTrashed()->get(); 

        DB::beginTransaction();
        try {
            $bar = $this->output->createProgressBar($predios->count());

            foreach ($predios as $predio) {
                $this->procesarDireccion($predio, 'NORTE', $predio->colindante_norte, $orientaciones, $tipos, $viasDb);
                $this->procesarDireccion($predio, 'SUR', $predio->colindante_sur, $orientaciones, $tipos, $viasDb);
                $this->procesarDireccion($predio, 'ESTE', $predio->colindante_este, $orientaciones, $tipos, $viasDb);
                $this->procesarDireccion($predio, 'OESTE', $predio->colindante_oeste, $orientaciones, $tipos, $viasDb);
                
                $bar->advance();
            }
            
            DB::commit();
            $bar->finish();
            $this->newLine();
            $this->info('Migración completada con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
        }
    }

    private function procesarDireccion($predio, $nombreOrientacion, $textoColindante, $orientaciones, $tipos, $viasDb)
    {
        if (empty($textoColindante)) return;

        // 1. Limpieza y Separación
        // Separar por " Y ", " y ", o comas. 
        $partes = preg_split('/\s+y\s+|\s*,\s*|\s+e\s+/i', $textoColindante, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($partes as $parte) {
            $parte = trim($parte);
            $tipoId = $tipos['OTRO']; // Default
            $viaId = null;
            $nombreONumero = $parte;

            // 2. Análisis Heurístico
            $parteUpper = strtoupper($parte);

            if (str_starts_with($parteUpper, 'LOTE')) {
                $tipoId = $tipos['LOTE'];
                // Extraer solo el número/texto después de "LOTE"
                $nombreONumero = trim(preg_replace('/^LOTES?\s*/i', '', $parte));
            } 
            elseif (
                str_contains($parteUpper, 'CALLE') || 
                str_contains($parteUpper, 'AV') || 
                str_contains($parteUpper, 'AVENIDA') || 
                str_contains($parteUpper, 'PASAJE') ||
                str_contains($parteUpper, 'C.') 
            ) {
                $tipoId = $tipos['VIA'];
                
                // Intentar buscar en la tabla de Vias existente
                $viaEncontrada = $viasDb->first(function($v) use ($parteUpper) {
                    // Si el nombre de la vía está contenido en el texto (ej: "ALIANZA" en "CALLE ALIANZA")
                    return str_contains($parteUpper, strtoupper($v->nombre)); 
                });

                if ($viaEncontrada) {
                    $viaId = $viaEncontrada->id;
                    $nombreONumero = null; 
                } else {
                    $nombreONumero = $parte; 
                }
            }
            elseif (str_contains($parteUpper, 'RIO')) {
                $tipoId = $tipos['RIO'];
            }
            elseif (str_contains($parteUpper, 'AREA VERDE') || str_contains($parteUpper, 'PLAZA') || str_contains($parteUpper, 'PARQUE')) {
                $tipoId = $tipos['AREA VERDE'];
            }
            elseif (str_contains($parteUpper, 'EQUIPAMIENTO') || str_contains($parteUpper, 'SEDE') || str_contains($parteUpper, 'ESCUELA')) {
                $tipoId = $tipos['EQUIPAMIENTO'];
            }

            // 3. Guardar (Evitar duplicados)
            $exists = PredioColindancia::where('predio_id', $predio->id)
                ->where('orientacion_id', $orientaciones[$nombreOrientacion])
                ->where('tipo_colindante_id', $tipoId)
                ->where('via_id', $viaId)
                ->where('nombre_o_numero', $nombreONumero)
                ->exists();

            if (!$exists) {
                PredioColindancia::create([
                    'predio_id' => $predio->id,
                    'orientacion_id' => $orientaciones[$nombreOrientacion],
                    'tipo_colindante_id' => $tipoId,
                    'via_id' => $viaId,
                    'nombre_o_numero' => $nombreONumero,
                ]);
            }
        }
    }
}