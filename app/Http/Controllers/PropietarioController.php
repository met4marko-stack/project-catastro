<?php

namespace App\Http\Controllers;

use App\Models\Propietario;
use App\Models\Persona;
use App\Models\Municipio;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\PdfToImage\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Illuminate\Support\Str;
use Intervention\Image\Exceptions\NotReadableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class PropietarioController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();

            // Query base con joins para poder buscar/ordenar en columnas relacionadas
            $query = Propietario::leftJoin('personas', 'propietarios.persona_id', '=', 'personas.id')
                ->leftJoin('municipios', 'propietarios.municipio_id', '=', 'municipios.id')
                ->select(
                    'propietarios.*',
                    DB::raw("CONCAT_WS(' ', personas.nombre, personas.primer_apellido, personas.segundo_apellido) AS nombre_completo"),
                    'personas.carnet as persona_carnet',
                    'personas.expedido as persona_expedido',
                    'municipios.nombre as municipio_nombre'
                );

            // Filtrar por municipio del usuario si aplica
            if ($user->hasRole('Admin-Municipal')) {
                if ($user->municipio_id) {
                    $query->where('propietarios.municipio_id', $user->municipio_id);
                } else {
                    // Opción segura: no aplicar filtro si no tiene municipio asignado
                    // (si quieres bloquear el acceso en ese caso, usa whereRaw('0 = 1');)
                }
            }

            // Usamos yajra datatables 
            return datatables()->of($query)
                ->addIndexColumn()
                ->addColumn('nombre_completo', function ($row) {
                    return $row->nombre_completo ?? trim(($row->nombre ?? '') . ' ' . ($row->primer_apellido ?? '') . ' ' . ($row->segundo_apellido ?? ''));
                })
                ->filterColumn('nombre_completo', function($query, $keyword) {
                    $sql = "CONCAT_WS(' ', personas.nombre, personas.primer_apellido, personas.segundo_apellido) ILIKE ?";
                    $query->whereRaw($sql, ["%{$keyword}%"]);
                })
                ->addColumn('carnet', function ($row) {
                    return trim(($row->persona_carnet ?? '') . ' ' . ($row->persona_expedido ?? ''));
                })
                ->filterColumn('carnet', function($query, $keyword) {
                    $query->where('personas.carnet', 'ILIKE', "%{$keyword}%");
                })
                ->addColumn('municipio', function ($row) {
                    return $row->municipio_nombre ?? '';
                })
                ->filterColumn('municipio', function($query, $keyword) {
                    $query->where('municipios.nombre', 'ILIKE', "%{$keyword}%");
                })
                ->addColumn('estado', function ($row) {
                    return $row->estado
                        ? '<span class="badge badge-success">Activo</span>'
                        : '<span class="badge badge-danger">Inactivo</span>';
                })
                ->addColumn('acciones', function ($row) {
                    $editUrl = route('admin.propietarios.edit', $row->id);
                    $deleteUrl = route('admin.propietarios.destroy', $row->id);
                    $restoreUrl = route('admin.propietarios.restore', $row->id);

                    $acciones = '';
                    if ($row->estado) {
                        $acciones .= '<a href="' . $editUrl . '" class="btn btn-sm btn-warning" title="Editar"><i class="fas fa-edit"></i></a> ';
                        $acciones .= '<form action="' . $deleteUrl . '" method="POST" class="d-inline form-delete" style="display:inline">'
                            . csrf_field()
                            . method_field('DELETE')
                            . '<button type="submit" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-trash"></i></button>'
                            . '</form>';
                    } else {
                        $acciones .= '<form action="' . $restoreUrl . '" method="POST" class="d-inline form-restore">'
                            . csrf_field()
                            . '<button type="submit" class="btn btn-sm btn-info" title="Reactivar"><i class="fas fa-undo"></i></button>'
                            . '</form>';
                    }
                    return $acciones;
                })
                ->rawColumns(['estado', 'acciones'])
                ->toJson();
        }

        return view('admin.propietarios.index');
    }

    public function create()
    {
        $municipios = Municipio::all();
        $expedidoOptions = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD', 'QR'];
        return view('admin.propietarios.create', compact('municipios', 'expedidoOptions'));
    }

    public function store(Request $request)
    {
        //\xdebug_info();
        //die('Revisando la información de Xdebug...');
        $request->validate([
            'nombre' => 'required|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'carnet' => 'nullable|string|max:255|unique:personas,carnet',
            'ci_fecha_caducidad' => 'nullable|date|required_if:ci_es_indefinido,false',
            'ci_es_indefinido' => 'nullable|boolean',
            'municipio_id' => Auth::user()->hasRole('Super-Admin') ? 'required|exists:municipios,id' : 'nullable',
        ]);

        try {
            DB::beginTransaction();

            $persona = Persona::create([
                'nombre' => Str::upper($request->nombre),
                'primer_apellido' => Str::upper($request->primer_apellido),
                'segundo_apellido' => Str::upper($request->segundo_apellido),
                'carnet' => $request->carnet,
                'expedido' => $request->expedido,
                'telefono' => $request->telefono,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'ci_es_indefinido' => $request->has('ci_es_indefinido'),
                'ci_fecha_caducidad' => $request->has('ci_es_indefinido') ? null : $request->ci_fecha_caducidad,
            ]);

            $municipio_id = Auth::user()->hasRole('Super-Admin')
                ? $request->municipio_id
                : Auth::user()->municipio_id;

            Propietario::create([
                'persona_id' => $persona->id,
                'municipio_id' => $municipio_id,
                'estado' => true,
            ]);

            DB::commit();
            return redirect()->route('admin.propietarios.index')->with('success', 'Propietario registrado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ocurrió un error. Es posible que el carnet ya exista o la persona ya esté registrada como propietaria en este municipio.']);
        }
    }

    public function edit(Propietario $propietario)
    {
        $municipios = Municipio::all();
        $expedidoOptions = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD', 'QR'];
        return view('admin.propietarios.edit', compact('propietario', 'municipios', 'expedidoOptions'));
    }

    public function update(Request $request, Propietario $propietario)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'carnet' => 'nullable|string|max:255|unique:personas,carnet,' . $propietario->persona_id,
            'ci_fecha_caducidad' => 'nullable|date|required_if:ci_es_indefinido,false',
            'ci_es_indefinido' => 'nullable|boolean',
            'municipio_id' => Auth::user()->hasRole('Super-Admin') ? 'required|exists:municipios,id' : 'nullable',
            'estado' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();
            $propietario->persona->update([
                'nombre' => Str::upper($request->nombre),
                'primer_apellido' => Str::upper($request->primer_apellido),
                'segundo_apellido' => Str::upper($request->segundo_apellido),
                'carnet' => $request->carnet,
                'expedido' => $request->expedido,
                'telefono' => $request->telefono,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'ci_es_indefinido' => $request->has('ci_es_indefinido'),
                'ci_fecha_caducidad' => $request->has('ci_es_indefinido') ? null : $request->ci_fecha_caducidad,
            ]);

            $propietario->estado = $request->estado;
            if (Auth::user()->hasRole('Super-Admin')) {
                $propietario->municipio_id = $request->municipio_id;
            }
            $propietario->save();

            DB::commit();
            return redirect()->route('admin.propietarios.index')->with('success', 'Propietario actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ocurrió un error al actualizar.']);
        }
    }

    /**
     * Desactiva un propietario (borrado lógico).
     */
    public function destroy(Propietario $propietario)
    {
        $propietario->estado = false;
        $propietario->save();

        return redirect()->route('admin.propietarios.index')->with('success', 'Propietario desactivado exitosamente.');
    }

    /**
     * Reactiva un propietario.
     */
    public function restore($id)
    {
        // Usamos findOrFail para asegurarnos de que el propietario exista
        $propietario = Propietario::findOrFail($id);
        $propietario->estado = true;
        $propietario->save();

        return redirect()->route('admin.propietarios.index')->with('success', 'Propietario reactivado exitosamente.');
    }

    ////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////

    public function procesarOcr(Request $request)
    {

        $request->validate([
            'documento_ci' => 'required|file|mimes:pdf,jpg,png,jpeg|max:10240',
        ]);

        $file = $request->file('documento_ci');
        $mimeType = $file->getMimeType();
        $fileContents = $file->get();

        // 2. Si es PDF, convertir a JPG
        if ($mimeType === 'application/pdf') {
            $tempPdfPath = $file->getRealPath();
            $tempDir = storage_path('app/public/ocr_temp');
            File::ensureDirectoryExists($tempDir);
            $tempJpgPath = $tempDir . '/' . uniqid() . '.jpg';

            try {
                (new Pdf($tempPdfPath))->resolution(300)->save($tempJpgPath);

                if (!File::exists($tempJpgPath) || File::size($tempJpgPath) === 0) {
                    throw new \Exception("La conversión de PDF a imagen falló.");
                }

                $fileContents = file_get_contents($tempJpgPath);
                $mimeType = 'image/jpeg';
                unlink($tempJpgPath);
            } catch (\Exception $e) {
                return response()->json(['error' => 'No se pudo convertir el PDF. Asegúrate de que Imagick y Ghostscript estén instalados.', 'details' => $e->getMessage()], 500);
            }
        }

        // 3. Preparar y enviar a la API de OpenRouter
        $base64File = base64_encode($fileContents);
        $prompt = $this->getPromptParaCarnetIdentidad();
        $apiKey = env('OPENROUTER_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'La API Key de OpenRouter no está configurada en .env.'], 500);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])->timeout(90)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'google/gemma-3-12b-it:free', // Un modelo robusto
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$base64File}"]]
                    ]
                ]
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        // 4. Procesar la respuesta
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

            // Si el campo 'expedido' existe y no es 'QR', lo mapeamos a su abreviatura
            if (isset($datosExtraidos['expedido']) && strtoupper($datosExtraidos['expedido']) !== 'QR') {
                $datosExtraidos['expedido'] = $this->mapearDepartamentoAAbreviatura($datosExtraidos['expedido']);
            }

            return response()->json($datosExtraidos);
        } else {
            return response()->json(['error' => 'Error en la comunicación con la API.', 'details' => $response->body()], $response->status());
        }
    }

    /**
     * Devuelve el prompt detallado para extraer datos de un carnet de identidad boliviano.
     */
    private function getPromptParaCarnetIdentidad(): string
    {
        return <<<PROMPT
Eres un asistente experto en digitalización de documentos de identidad de Bolivia. Analiza la imagen y extrae la información clave. Responde ÚNICAMENTE en formato JSON válido, sin explicaciones, comentarios o markdown.

Si un dato no se encuentra, utiliza `null` como valor.

Reglas importantes:
1.  **Nombre Completo:** La estructura más común en Bolivia es `Nombre(s) + Primer Apellido + Segundo Apellido`. Por lo tanto, tu prioridad es identificar los dos apellidos al final del nombre completo. **Sin embargo, debes ser capaz de identificar la excepción: si una persona tiene dos nombres de pila y un solo apellido (ej. "Ana Sofia Perez"), entonces la última palabra es el `primer_apellido`, el `segundo_apellido` es `null`, y las palabras anteriores conforman el `nombre`.** Usa tu juicio para diferenciar entre un segundo nombre de pila y un primer apellido en nombres de tres palabras.

2.  **Fechas:** Todas las fechas deben estar en formato AAAA-MM-DD.
3.  **Expedido:** Si el carnet tiene un código QR, el valor para "expedido" debe ser "QR". Si no, usa la ciudad que se muestra (ej: "LA PAZ").
4.  **Expiración Indefinida:** Si la fecha de expiración dice "INDEFINIDO", `ci_es_indefinido` debe ser `true` y `ci_fecha_caducidad` debe ser `null`.

El formato JSON de salida debe ser el siguiente, usando como ejemplo el caso de dos nombres y un apellido:
{
  "nombre": "ANA SOFIA",
  "primer_apellido": "PEREZ",
  "segundo_apellido": "MAMANI",
  "carnet": "1234567",
  "expedido": "LP",
  "fecha_nacimiento": "1990-01-01",
  "ci_fecha_caducidad": "2028-01-01",
  "ci_es_indefinido": false
}
PROMPT;
    }

    private function mapearDepartamentoAAbreviatura(?string $nombreCompleto): ?string
    {
        if ($nombreCompleto === null) {
            return null;
        }

        $mapa = [
            'LA PAZ' => 'LP',
            'COCHABAMBA' => 'CB',
            'SANTA CRUZ' => 'SC',
            'ORURO' => 'OR',
            'POTOSI' => 'PT',
            'CHUQUISACA' => 'CH',
            'TARIJA' => 'TJ',
            'BENI' => 'BE',
            'PANDO' => 'PD',
        ];

        // Normalizamos el input (mayúsculas y sin acentos) para una coincidencia más robusta
        $nombreNormalizado = strtoupper(str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $nombreCompleto));

        return $mapa[$nombreNormalizado] ?? null; // Devuelve la abreviatura o null si no hay coincidencia
    }
}
