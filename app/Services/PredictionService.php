<?php

namespace App\Services;

use App\Models\Tramite;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PredictionService
{
    protected $apiUrl;

    public function __construct()
    {
        // La URL de tu API de Flask. La guardamos en el .env por seguridad y flexibilidad.
        $this->apiUrl = env('ML_API_URL', 'http://127.0.0.1:5000/predict');
    }

    public function getPredictions(Tramite $tramite): ?array
    {
        try {
            $data = [
                'tipo_tramite_id' => $tramite->tramite_tipo_id,
                'año_ingreso' => $tramite->fecha_ingreso->year,
                'mes_ingreso' => $tramite->fecha_ingreso->month,
                'sup_levantamiento' => $tramite->predio->sup_levantamiento ?? 0,
                'sup_testimonio' => $tramite->predio->sup_testimonio ?? 0,
                'es_prop_horizontal' => (bool) $tramite->predio->propiedad_horizontal,
                'cantidad_propietarios' => $tramite->predio->propietarios->count(),
                'servicios_basicos_count' => 
                    (int)$tramite->predio->agua_potable + (int)$tramite->predio->energia_electrica +
                    (int)$tramite->predio->alcantarillado + (int)$tramite->predio->alumbrado_publico +
                    (int)$tramite->predio->gas_domiciliario,
                'discrepancia_superficies' => abs(($tramite->predio->sup_levantamiento ?? 0) - ($tramite->predio->sup_testimonio ?? 0)),
            ];

            $response = Http::timeout(5)->post($this->apiUrl, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error en API de ML: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('No se pudo conectar a la API de ML: ' . $e->getMessage());
            return null;
        }
    }
}