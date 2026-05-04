<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PredioColindancia;
use App\Models\TipoColindante;
use App\Models\Via;
use Illuminate\Support\Str;

class CorregirViasColindancias extends Command
{
    protected $signature = 'catastro:corregir-vias-colindancias';
    protected $description = 'Intenta vincular colindancias de tipo VIA que no tienen ID asignado normalizando nombres';

    public function handle()
    {
        $this->info('Iniciando corrección de vías...');

        $tipoVia = TipoColindante::where('nombre', 'VIA')->first();
        if (!$tipoVia) {
            $this->error('No se encontró el tipo VIA.');
            return;
        }

        // Cargar todas las vías para búsqueda en memoria
        $vias = Via::all();

        // Obtener colindancias de tipo VIA que no tienen via_id asignado
        $colindanciasPendientes = PredioColindancia::where('tipo_colindante_id', $tipoVia->id)
            ->whereNull('via_id')
            ->whereNotNull('nombre_o_numero')
            ->get();

        $this->info("Encontradas {$colindanciasPendientes->count()} colindancias pendientes.");
        $bar = $this->output->createProgressBar($colindanciasPendientes->count());

        $corregidos = 0;

        foreach ($colindanciasPendientes as $colindancia) {
            $nombreOriginal = strtoupper(trim($colindancia->nombre_o_numero));
            
            // 1. Normalización
            $nombreNormalizado = $this->normalizarNombreVia($nombreOriginal);

            // 2. Búsqueda Exacta con nombre normalizado
            $viaEncontrada = $vias->first(function ($via) use ($nombreNormalizado) {
                return strtoupper(trim($via->nombre)) === $nombreNormalizado;
            });

            // 3. Búsqueda "Contiene" (Si la normalización falló o es parcial)
            // Solo si tiene longitud razonable para evitar falsos positivos
            if (!$viaEncontrada && strlen($nombreNormalizado) > 4) {
                $viaEncontrada = $vias->first(function ($via) use ($nombreNormalizado) {
                    $viaNombre = strtoupper($via->nombre);
                    return str_contains($viaNombre, $nombreNormalizado) 
                        || str_contains($nombreNormalizado, $viaNombre);
                });
            }

            if ($viaEncontrada) {
                $colindancia->via_id = $viaEncontrada->id;
                $colindancia->nombre_o_numero = null; // Limpiamos el texto ya que tenemos el ID
                $colindancia->save();
                $corregidos++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Proceso finalizado. Se corrigieron {$corregidos} registros.");
    }

    private function normalizarNombreVia($nombre)
    {
        // Reemplazos comunes
        $reemplazos = [
            '/^AV\.\s+/' => 'AVENIDA ',
            '/^AV\s+/'   => 'AVENIDA ',
            '/^C\.\s+/'  => 'CALLE ',
            '/^C\s+/'    => 'CALLE ',
            '/^PJE\.\s+/' => 'PASAJE ',
            '/^PJE\s+/'   => 'PASAJE ',
            '/\s+/'      => ' ', // Quitar espacios dobles
        ];

        return trim(preg_replace(array_keys($reemplazos), array_values($reemplazos), $nombre));
    }
}