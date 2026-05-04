<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tramite;
use App\Models\TramiteEstado;
use Illuminate\Support\Carbon;

class ArchivarTramitesVencidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:archivar-tramites-vencidos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Obtener los IDs de los estados
        $estadoParalizado = TramiteEstado::where('nombre', 'PARALIZADO')->first();
        $estadoArchivado = TramiteEstado::where('nombre', 'ARCHIVADO')->first();

        if (!$estadoParalizado || !$estadoArchivado) {
            // No se pueden ejecutar las reglas si los estados no existen
            return;
        }

        // 2. Buscar trámites vencidos (más de 10 días en estado paralizado)
        $fechaLimite = Carbon::now()->subDays(10);

        $tramitesVencidos = Tramite::where('estado_id', $estadoParalizado->id)
            ->where('fecha_paralizado', '<=', $fechaLimite)
            ->get();

        // 3. Actualizar y Archivar
        foreach ($tramitesVencidos as $tramite) {
            $tramite->estado_id = $estadoArchivado->id; 
            $tramite->save();
            $tramite->delete(); 
        }

        $this->info("Se han archivado " . count($tramitesVencidos) . " trámites vencidos.");
    }
}
