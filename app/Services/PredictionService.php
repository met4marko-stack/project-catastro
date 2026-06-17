<?php

namespace App\Services;

use App\Models\Tramite;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para conectarse con la API de Machine Learning (Python/Flask).
 *
 * Esta clase se encarga de preparar los datos de un trámite, enviarlos
 * a la API de predicción y procesar la respuesta que contiene
 * tanto el Nivel de Riesgo como los Días de Resolución estimados.
 */
class PredictionService
{
    /**
     * La URL base de la API de Flask (ej. http://localhost:5000)
     * @var string
     */
    protected $apiUrl;

    /**
     * El endpoint específico para las predicciones.
     * @var string
     */
    protected $endpoint;

    /**
     * Carga la URL de la API desde el archivo .env
     */
    public function __construct()
    {
        $this->apiUrl = env('ML_API_URL', 'http://127.0.0.1:5000');
        $this->endpoint = '/predict'; // El endpoint único que sirve ambos modelos
    }

    /**
     * Obtiene AMBAS predicciones (Riesgo y Días) desde la API de ML
     *
     * @param Tramite $tramite El objeto del trámite a evaluar.
     * @return array|null Un array con los resultados de la predicción o un fallback.
     */
    public function getPredictions(Tramite $tramite): ?array
    {
        try {
            // 1. Cargar las relaciones necesarias para no hacer N+1 queries
            $tramite->loadMissing(['predio.propietarios', 'tipo']);
            $predio = $tramite->predio;
            
            // 2. Validar que el trámite tiene un predio asociado
            if (!$predio) {
                Log::warning("Trámite {$tramite->id} no tiene predio asociado. No se puede predecir.");
                return $this->getPrediccionPorDefecto();
            }

            // 3. Contar los servicios básicos desde el modelo Predio
            $serviciosCount = ($predio->agua_potable ? 1 : 0) +
                              ($predio->energia_electrica ? 1 : 0) +
                              ($predio->alcantarillado ? 1 : 0) +
                              ($predio->alumbrado_publico ? 1 : 0) +
                              ($predio->gas_domiciliario ? 1 : 0);
            
            // 4. Preparar el paquete de datos COMPLETO que espera la API
            // Esto incluye los 5 factores para RIESGO y los 9 factores para DÍAS
            $datosParaML = [
                // --- Factores Comunes (para ambos modelos) ---
                'tipo_tramite_id' => $tramite->tramite_tipo_id,
                'cantidad_propietarios' => $predio->propietarios->count(),
                'es_prop_horizontal' => $predio->propiedad_horizontal ? 1 : 0,
                'servicios_basicos_count' => $serviciosCount,
                'superficie_levantamiento' => $predio->sup_levantamiento ?? 0,
                
                // --- Factores Adicionales (solo para el modelo de DÍAS) ---
                'año_ingreso' => $tramite->fecha_ingreso->year,
                'mes_ingreso' => $tramite->fecha_ingreso->month,
                'sup_testimonio' => $predio->sup_testimonio ?? 0,
                'discrepancia_superficies' => abs(($predio->sup_levantamiento ?? 0) - ($predio->sup_testimonio ?? 0)),
            ];

            Log::info('Enviando datos a ML API (Días + Riesgo):', $datosParaML);

            // 5. Llamar al endpoint único /predict
            $response = Http::timeout(10) // 10 segundos de timeout
                ->post($this->apiUrl . $this->endpoint, $datosParaML);

            // 6. Procesar la respuesta
            if ($response->successful()) {
                $resultado = $response->json();
                Log::info('Predicción ML recibida:', $resultado);
                return $resultado;
            } else {
                // Si la API falla (ej. 500 en Python)
                Log::error('Error en API ML:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return $this->getPrediccionPorDefecto();
            }

        } catch (\Exception $e) {
            // Si Laravel no puede conectarse a la API (ej. Flask está caído)
            Log::error('Excepción al llamar al servicio ML: ' . $e->getMessage());
            return $this->getPrediccionPorDefecto();
        }
    }

    /**
     * Genera una lista de recomendaciones legibles basadas en los factores de riesgo.
     *
     * @param array $factores La lista de factores devuelta por la API.
     * @return array Una lista de strings con recomendaciones.
     */
    public function generarRecomendacionesML($factores)
    {
        $recomendaciones = [];

        // Asegurarse de que $factores sea un array
        if (!is_array($factores)) {
            return ["No se pudieron generar recomendaciones."];
        }

        foreach ($factores as $factor) {
            if (str_contains($factor, 'Múltiples propietarios')) {
                $recomendaciones[] = "Coordinar reunión con todos los propietarios para firmas.";
            }
            if (str_contains($factor, 'Propiedad horizontal')) {
                $recomendaciones[] = "Solicitar reglamento de condominio y actas de asamblea.";
            }
            if (str_contains($factor, 'Tipo de trámite complejo') || str_contains($factor, 'División / P.H.')) {
                $recomendaciones[] = "Asignar a Inspector-Técnico para revisión prioritaria.";
            }
            if (str_contains($factor, 'Servicios básicos insuficientes')) {
                $recomendaciones[] = "Requerir certificados de servicios o inspección en sitio.";
            }
            if (str_contains($factor, 'Superficie muy grande')) {
                $recomendaciones[] = "Realizar inspección técnica de campo y verificar normativas de uso de suelo.";
            }
            if (str_contains($factor, 'Superficie muy pequeña')) {
                $recomendaciones[] = "Verificar cumplimiento de lote mínimo y posibles subdivisiones no registradas.";
            }
        }

        // Si no se activó ninguna regla de riesgo
        if (empty($recomendaciones) || (isset($factores[0]) && $factores[0] == "Trámite con características estándar.")) {
            $recomendaciones = ["Trámite de baja complejidad. Procesar según flujo normal."];
        }

        return array_unique($recomendaciones);
    }

    /**
     * Devuelve una respuesta de "fallback" en caso de que la API de ML falle.
     * Esto asegura que la aplicación de Laravel no se rompa y muestre un
     * estado de advertencia al usuario.
     *
     * @return array
     */
    private function getPrediccionPorDefecto()
    {
        return [
            'modelo_riesgo' => 'Sistema de respaldo',
            'nivel_riesgo' => 'Medio',
            'probabilidad_bajo' => 33.3,
            'probabilidad_medio' => 33.3,
            'probabilidad_alto' => 33.3,
            'factores_identificados' => ['Servicio de predicción no disponible. Mostrando reglas de respaldo.'],
            'color_riesgo' => 'warning',
            'dias_prediccion' => 15 // Valor de fallback para días
        ];
    }
}