<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PredioColindancia;
use App\Models\TipoColindante;

class ListarColindanciasHuerfanas extends Command
{
    protected $signature = 'catastro:listar-huerfanas';
    protected $description = 'Lista los nombres de vías en colindancias que no se pudieron vincular';

    public function handle()
    {
        $tipoVia = TipoColindante::where('nombre', 'VIA')->first();
        
        $huerfanas = PredioColindancia::where('tipo_colindante_id', $tipoVia->id)
            ->whereNull('via_id')
            ->whereNotNull('nombre_o_numero')
            ->select('nombre_o_numero')
            ->distinct()
            ->orderBy('nombre_o_numero')
            ->get();

        $this->info("Se encontraron {$huerfanas->count()} nombres únicos de vías no vinculadas:");
        $this->newLine();

        foreach ($huerfanas as $item) {
            $this->line("- " . $item->nombre_o_numero);
        }
    }
}