<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Spatie\PdfToImage\Pdf;
use Illuminate\Support\Facades\File;
use Imagick;

class OcrAIController extends Controller
{
    /**
     * Procesa un documento (PDF o imagen) para extraer datos estructurados usando una IA de visión.
     */
    public function procesarDocumento(Request $request)
    {
        // 1. Validar que se haya subido un archivo válido
        $request->validate([
            'documento' => 'required|file|mimes:pdf,jpg,png,jpeg|max:10240', // PDF e imágenes hasta 10MB
        ]);

        $file = $request->file('documento');
        $mimeType = $file->getMimeType();
        $fileContents = $file->get();

        // 2. Si es un PDF, lo convierte a JPG antes de continuar
        if ($mimeType === 'application/pdf') {
            $tempPdfPath = $file->getRealPath();
            $tempDir = storage_path('app/public/ocr_temp');

            // Asegurarse de que el directorio temporal exista
            File::ensureDirectoryExists($tempDir);

            $tempJpgPath = $tempDir . '/' . uniqid() . '.jpg';

            try {
                // --- INICIO DE LA CORRECCIÓN ---
                // Se establece la resolución y luego se guarda la imagen.
                (new Pdf($tempPdfPath))
                    ->resolution(300)
                    ->save($tempJpgPath);
                // --- FIN DE LA CORRECCIÓN ---
                
                if (!File::exists($tempJpgPath) || File::size($tempJpgPath) === 0) {
                    throw new \Exception("La conversión de PDF a imagen falló o el archivo resultante está vacío.");
                }

                $fileContents = file_get_contents($tempJpgPath);
                $mimeType = 'image/jpeg';
                
                // Limpiar el archivo temporal
                unlink($tempJpgPath);

            } catch (\Exception $e) {
                return response()->json(['error' => 'No se pudo convertir el PDF. Asegúrate de que Imagick y Ghostscript estén instalados y configurados en tu servidor.', 'details' => $e->getMessage()], 500);
            }
        }

        // 3. Prepara los datos para la API de OpenRouter
        $base64File = base64_encode($fileContents);
        $prompt = $this->getPromptParaPlanoCatastral();
        $apiKey = env('OPENROUTER_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'La API Key de OpenRouter no está configurada en el archivo .env.'], 500);
        }

        // 4. Realiza la llamada a la API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'), 
            'X-Title' => config('app.name'),
        ])->timeout(90)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'google/gemma-3-12b-it:free',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$base64File}"]]
                    ]
                ]
            ],
            'max_tokens' => 4096,
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
        ]);

        // 5. Procesa la respuesta de la API
        if ($response->successful()) {
            $contentString = $response->json('choices.0.message.content');
            
            if (!$contentString) {
                return response()->json(['error' => 'La API no devolvió contenido útil.'], 500);
            }

            // Limpiar la respuesta para extraer solo el JSON
            $jsonString = $contentString;
            if (strpos(trim($jsonString), '```json') === 0) {
                $jsonString = str_replace('```json', '', $jsonString);
                $jsonString = str_replace('```', '', $jsonString);
            }

            $datosExtraidos = json_decode(trim($jsonString), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'La IA no devolvió un JSON válido.', 'raw_response' => $contentString], 400);
            }

            return response()->json($datosExtraidos);
        } else {
            return response()->json(['error' => 'Error en la comunicación con la API de OpenRouter.', 'details' => $response->body()], $response->status());
        }
    }

    /**
     * Devuelve el prompt detallado para extraer datos de un plano catastral.
     */
    private function getPromptParaPlanoCatastral(): string
    {
        return <<<PROMPT
Eres un asistente experto en análisis de documentos catastrales de Bolivia. Analiza la siguiente imagen de un plano de lote y extrae la información clave. Responde únicamente en formato JSON válido, sin incluir explicaciones, comentarios, markdown (```json) o texto introductorio.

Si un dato no se encuentra en el plano, utiliza `null` como valor para esa clave.

El formato JSON debe ser el siguiente:
{
  "propietario": "Nombre completo del propietario",
  "ubicacion": {
    "departamento": "LA PAZ",
    "provincia": "AROMA",
    "municipio": "AYO AYO",
    "centro_poblado": "TOLAR"
  },
  "identificacion_lote": {
    "manzano": "5",
    "lote": "7",
    "superficie_m2": 227.97
  },
  "colindantes": {
    "norte": "LOTE 8",
    "sur": "LOTE 6",
    "este": "LOTE 17 Y 18",
    "oeste": "CALLE ANTOFAGASTA"
  },
  "coordenadas_utm": [
    { "punto": 1, "este": 599634.909, "norte": 8119260.809 },
    { "punto": 2, "este": 599615.604, "norte": 8119247.750 },
    { "punto": 3, "este": 599610.251, "norte": 8119255.769 },
    { "punto": 4, "este": 599629.164, "norte": 8119269.012 }
  ]
}
PROMPT;
    }
}