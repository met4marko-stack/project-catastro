<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Tramite;
use Carbon\Carbon;

class ExportarDatosML extends Command
{
    protected $signature = 'ml:exportar-datos';
    protected $description = 'Extrae los trámites finalizados de la BD y genera el CSV para Machine Learning';

    public function handle()
    {
        $this->info('Iniciando extracción de datos históricos...');

        // Solo tomamos trámites que ya tengan una fecha de conclusión (históricos reales)
        // NOTA: Si en tu sistema de pruebas aún no tienes trámites concluidos, 
        // puedes quitar momentáneamente el "whereNotNull" para probar.
        $tramites = Tramite::with(['predio.propietarios', 'estado'])
                    ->whereNotNull('fecha_conclusion') 
                    ->get();

        // Ruta donde guardaremos el CSV (Apuntando a la carpeta de Python)
        $csvPath = base_path('../python_catastro/modules/proyecto_ml/data/dataset_historico_produccion.csv');
        $file = fopen($csvPath, 'w');

        // Cabeceras del CSV
        fputcsv($file, [
            'tipo_tramite_id', 'cantidad_propietarios', 'es_prop_horizontal', 
            'servicios_basicos_count', 'superficie_levantamiento', 'discrepancia_superficies', 
            'ESTADO_FINAL_HISTORICO', 'año_ingreso', 'mes_ingreso', 'sup_testimonio', 'DIAS_RESOLUCION'
        ]);

        $contador = 0;

        foreach ($tramites as $tramite) {
            $predio = $tramite->predio;
            if (!$predio) continue;

            // 1. Calcular Cantidad de Propietarios Activos
            $cantidadPropietarios = $predio->propietarios()->count();
            if ($cantidadPropietarios == 0) $cantidadPropietarios = 1; // Mínimo 1 por defecto

            // 2. Calcular Servicios Básicos
            $servicios = 0;
            if ($predio->agua_potable) $servicios++;
            if ($predio->energia_electrica) $servicios++;
            if ($predio->alcantarillado) $servicios++;
            if ($predio->alumbrado_publico) $servicios++;
            if ($predio->gas_domiciliario) $servicios++;

            // 3. Superficies y Discrepancias
            $supLevantamiento = $predio->sup_levantamiento ?? 0;
            $supTestimonio = $predio->sup_testimonio ?? 0;
            $discrepancia = abs($supLevantamiento - $supTestimonio);

            // 4. Etiqueta de Riesgo Real (Basado en el historial de estados)
            $estadoNombre = strtolower($tramite->estado->nombre ?? '');
            $estadoFinalML = 0; // Por defecto: Bajo riesgo (Aprobado)
            
            // Lógica de mapeo de estados. Ajusta los nombres según los que uses en tu base de datos.
            if (str_contains($estadoNombre, 'observado') || str_contains($estadoNombre, 'subsanado')) {
                $estadoFinalML = 1; // Riesgo Medio
            } elseif (str_contains($estadoNombre, 'rechazado') || str_contains($estadoNombre, 'anulado') || str_contains($estadoNombre, 'paralizado')) {
                $estadoFinalML = 2; // Riesgo Alto
            }

            // 5. Fechas y Días
            $fechaIngreso = Carbon::parse($tramite->fecha_ingreso);
            $fechaConclusion = Carbon::parse($tramite->fecha_conclusion);
            $diasResolucion = $fechaIngreso->diffInDays($fechaConclusion);

            // Escribir fila en el CSV
            fputcsv($file, [
                $tramite->tramite_tipo_id,
                $cantidadPropietarios,
                $predio->propiedad_horizontal ? 1 : 0,
                $servicios,
                $supLevantamiento,
                $discrepancia,
                $estadoFinalML,
                $fechaIngreso->year,
                $fechaIngreso->month,
                $supTestimonio,
                $diasResolucion
            ]);

            $contador++;
        }

        fclose($file);
        $this->info("¡Extracción completada! Se exportaron $contador registros históricos.");
    }
}