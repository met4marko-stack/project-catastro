<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\TramiteDocumento;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ValidarDocumentoIA implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $documentoId;
    protected $contexto;

    public function __construct($documentoId, $contexto)
    {
        $this->documentoId = $documentoId;
        $this->contexto = $contexto;
    }

    public function handle()
    {
        $documento = TramiteDocumento::find($this->documentoId);
        if (!$documento) return;

        // Recuperar archivo del storage local
        $archivoPath = Storage::disk('documentos_locales')->path($documento->ruta_archivo);

        try {
            // Llamar a tu API de Python
            $response = Http::timeout(60)->attach(
                'archivo',
                file_get_contents($archivoPath),
                $documento->nombre_original
            )->post('http://127.0.0.1:8001/api/v1/ocr/validar-documento', [
                'id_requisito' => $documento->requisito_id,
                'contexto_db'  => json_encode($this->contexto)
            ]);

            if ($response->successful()) {
                $res = $response->json();

                if (isset($res['resultado']['documento_valido'])) {
                    $analisis = $res['resultado']['analisis'];

                    $observacion = "";
                    foreach ($analisis as $campo => $data) {
                        $icono = $data['valido'] ? '✅' : '❌';
                        $observacion .= "{$icono} " . strtoupper($campo) . ": {$data['detalle']}\n";
                    }

                    $documento->observaciones = $observacion;
                    $documento->save();
                }
            } else {
                Log::error("Error del validador: " . $response->body());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error de conexión con el validador: " . $e->getMessage());

            // ACTUALIZAMOS LA VISTA PARA QUE EL USUARIO VEA QUE FALLÓ
            if ($documento) {
                $documento->observaciones = "❌ Error de conexión: No se pudo contactar al motor de validación.";
                $documento->save();
            }
        }
    }
}
