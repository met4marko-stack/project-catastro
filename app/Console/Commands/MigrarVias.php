<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Via;
use App\Models\TipoVia;
use Illuminate\Support\Facades\DB;

class MigrarVias extends Command
{
    protected $signature = 'catastro:migrar-vias';
    protected $description = 'Normaliza los nombres de las vías separando tipo y nombre específico';

    public function handle()
    {
        $this->info('Iniciando normalización de vías...');

        $tipos = TipoVia::all();
        $vias = Via::all();

        DB::beginTransaction();
        try {
            $bar = $this->output->createProgressBar($vias->count());

            foreach ($vias as $via) {
                $nombreCompleto = strtoupper(trim($via->nombre));
                $tipoId = null;
                $nombreEspecifico = $nombreCompleto;

                // Buscar si empieza con algún tipo conocido
                foreach ($tipos as $tipo) {
                    $prefijo = strtoupper($tipo->nombre) . ' ';
                    if (str_starts_with($nombreCompleto, $prefijo)) {
                        $tipoId = $tipo->id;
                        // Quitar el prefijo del nombre
                        $nombreEspecifico = trim(substr($nombreCompleto, strlen($prefijo)));
                        break; 
                    }
                }

                if (!$tipoId) {
                    // Buscar el tipo CALLE por defecto si no se detectó prefijo
                    $tipoCalle = $tipos->firstWhere('nombre', 'CALLE');
                    $tipoId = $tipoCalle->id;
                }

                $via->tipo_via_id = $tipoId;
                $via->nombre_especifico = $nombreEspecifico;
                $via->save();

                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine();
            $this->info('Normalización de vías completada.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
        }
    }
}