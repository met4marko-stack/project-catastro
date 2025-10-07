<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use App\Models\Planimetria;
use App\Models\Predio;
use App\Models\Propietario;
use App\Models\Persona;
use App\Models\Via;
use App\Models\MaterialVia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Spatie\PdfToImage\Pdf;
use Clickbar\Magellan\Data\Geometries\MultiPolygon;
use Clickbar\Magellan\Data\Geometries\Polygon;
use Clickbar\Magellan\Data\Geometries\Point;
use Clickbar\Magellan\Data\Geometries\LineString;


class PredioController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $userAuth = Auth::user();

            // Consulta base con relaciones necesarias
            $query = Predio::with(['municipio', 'planimetria', 'propietarios.persona']);

            // Filtrar por municipio para Admin-Municipal
            if ($userAuth->hasRole('Admin-Municipal')) {
                $query->where('municipio_id', $userAuth->municipio_id);
            }

            return datatables()->eloquent($query)
                ->addIndexColumn() // Añade la columna de numeración secuencial
                ->addColumn('propietarios', function (Predio $predio) {
                    // Concatena los nombres de los propietarios
                    return $predio->propietarios->map(function ($propietario) {
                        return $propietario->persona->nombre_completo;
                    })->implode('<br>');
                })
                ->addColumn('planimetria', function (Predio $predio) {
                    return $predio->planimetria->codigo ?? 'N/A';
                })
                ->addColumn('acciones', function (Predio $predio) {
                    $editUrl = route('admin.predios.edit', $predio);
                    $deleteUrl = route('admin.predios.destroy', $predio);

                    // Genera el HTML para los botones de acción
                    return '<a href="' . $editUrl . '" class="btn btn-sm btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                        <form action="' . $deleteUrl . '" method="POST" class="d-inline form-delete">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-sm btn-danger" title="Desactivar"><i class="fas fa-trash"></i></button>
                        </form>';
                })
                ->rawColumns(['propietarios', 'acciones']) // Indica que estas columnas contienen HTML
                ->toJson();
        }

        return view('admin.predios.index');
    }

    public function create()
    {
        $municipios = Municipio::all();
        $planimetrias = Planimetria::all();
        $propietarios = Propietario::with('persona')->where('estado', true)->get();
        $prediosPadre = Predio::where('propiedad_horizontal', true)->get();
        $predio = new Predio();
        $vias = Via::all(); // O filtrar por municipio si es necesario
        $materialesVias = MaterialVia::all();

        return view('admin.predios.create', compact('predio', 'municipios', 'planimetrias', 'propietarios', 'prediosPadre', 'vias', 'materialesVias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // Identificación y Jerarquía
            'inmueble_padre_id' => 'nullable|integer|exists:predios,id',
            'propiedad_horizontal' => 'nullable|boolean',
            'numero_unidad' => 'nullable|string|max:20|required_with:inmueble_padre_id',
            'codigo_catastral' => 'required|string|max:255|unique:predios,codigo_catastral',
            'numero_matricula_folio' => 'nullable|string|max:255',
            'numero_plano' => 'nullable|string|max:50',

            // Ubicación
            'manzano' => 'nullable|string|max:20',
            'lote' => 'nullable|string|max:20',
            'provincia' => 'nullable|string|max:255',
            'centro_poblado' => 'nullable|string|max:255',
            'zona' => 'nullable|string|max:255',

            // Superficies y Medidas
            'sup_levantamiento' => 'nullable|numeric|min:0',
            'sup_testimonio' => 'nullable|numeric|min:0',
            'sup_construida' => 'nullable|numeric|min:0',
            'sup_afectada' => 'nullable|numeric|min:0',
            'sup_util' => 'nullable|numeric|min:0',
            'frente_principal' => 'nullable|numeric|min:0',

            // Coordenadas
            'coordenadas_text' => 'nullable|json',

            // Servicios y Características
            'agua_potable' => 'nullable|boolean',
            'energia_electrica' => 'nullable|boolean',
            'alcantarillado' => 'nullable|boolean',
            'alumbrado_publico' => 'nullable|boolean',
            'gas_domiciliario' => 'nullable|boolean',
            'material_via' => 'nullable|string|max:255',
            'forma_lote' => 'nullable|string|in:Regular,Irregular',
            'id_material_via' => 'nullable|integer|exists:materiales_vias,id', // Valida contra la tabla
            'via_id' => 'nullable|integer|exists:vias,id', // Valida contra la tabla

            // Fotografías
            'fotografia_uno' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_dos' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_tres' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_cuatro' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_cinco' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // Colindantes
            'colindante_norte' => 'nullable|string|max:255',
            'colindante_sur' => 'nullable|string|max:255',
            'colindante_este' => 'nullable|string|max:255',
            'colindante_oeste' => 'nullable|string|max:255',

            // Relaciones
            'planimetria_id' => 'required|integer|exists:planimetrias,id',
            'propietarios' => 'required|array|min:1',
            'propietarios.*' => 'integer|exists:propietarios,id',
        ]);

        try {
            DB::beginTransaction();

            // 1. Prepara los datos del predio
            $data = $request->except(['propietarios', 'coordenadas_text', '_token', '_method', 'fotografia_uno', 'fotografia_dos', 'fotografia_tres', 'fotografia_cuatro', 'fotografia_cinco']);

            $data['municipio_id'] = Auth::user()->hasRole('Admin-Municipal') ? Auth::user()->municipio_id : $request->municipio_id;

            // Convertir checkboxes a booleanos
            $data['propiedad_horizontal'] = $request->has('propiedad_horizontal');
            $data['agua_potable'] = $request->has('agua_potable');
            $data['energia_electrica'] = $request->has('energia_electrica');
            $data['alcantarillado'] = $request->has('alcantarillado');
            $data['alumbrado_publico'] = $request->has('alumbrado_publico');
            $data['gas_domiciliario'] = $request->has('gas_domiciliario');

            if ($request->filled('forma_lote')) {
                $data['forma_lote'] = ($request->input('forma_lote') === 'Regular');
            }

            // 2. Maneja las cargas de archivos de fotografías
            if ($request->hasFile('fotografias')) {
                $photoFields = ['fotografia_uno', 'fotografia_dos', 'fotografia_tres', 'fotografia_cuatro', 'fotografia_cinco'];
                foreach ($request->file('fotografias') as $key => $file) {
                    if ($key < count($photoFields)) {
                        $path = $file->store('predio_fotos', 'public');
                        $data[$photoFields[$key]] = $path;
                    }
                }
            }

            // CAMBIO: Creación de objeto geoespacial
            if ($request->filled('coordenadas_text')) {
                $points = [];
                $coordenadasArray = json_decode($request->input('coordenadas_text'), true);
                foreach ($coordenadasArray as $coord) {
                    if (is_numeric($coord['este']) && is_numeric($coord['norte'])) {
                        // Creamos puntos 3D con Z=0 y M=0 para cumplir con el tipo de la BD (MultiPolygonZM)
                        $points[] = Point::make((float)$coord['este'], (float)$coord['norte'], 0, 0);
                    }
                }

                if (count($points) > 2) {
                    if (!$this->pointsAreEqual($points[0], end($points))) {
                        $points[] = $points[0]; // Cierra el polígono
                    }
                    $lineString = new LineString($points);
                    $polygon = new Polygon([$lineString]); // Crea un Polygon
                    // Envuelve el Polygon dentro de un MultiPolygon y asigna el SRID correcto
                    $data['coordenadas'] = new MultiPolygon([$polygon], 32719);
                }
            }

            // 4. Crea el predio
            $predio = Predio::create($data);

            // 5. Asocia los propietarios en la tabla pivote
            if ($request->has('propietarios')) {
                $predio->propietarios()->attach($request->propietarios, [
                    'estado' => 'Propietario Actual',
                    'fecha_inicio' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return redirect()->route('admin.predios.index')->with('success', 'Predio registrado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ocurrió un error al registrar el predio: ' . $e->getMessage()]);
        }
    }

    /**
     * Compara dos objetos Point con una tolerancia para decimales.
     */
    private function pointsAreEqual(Point $a, Point $b, float $epsilon = 1e-9): bool
    {
        // El paquete Magellan usa getX() y getY() para coordenadas proyectadas como UTM
        $ax = $a->getX();
        $ay = $a->getY();
        $bx = $b->getX();
        $by = $b->getY();

        return (abs($ax - $bx) < $epsilon) && (abs($ay - $by) < $epsilon);
    }

    public function edit(Predio $predio)
    {
        $municipios = Municipio::all();
        $planimetrias = Planimetria::all();
        $propietarios = Propietario::with('persona')->where('estado', true)->get();
        $prediosPadre = Predio::where('propiedad_horizontal', true)->where('id', '!=', $predio->id)->get();
        $vias = Via::all();
        $materialesVias = MaterialVia::all();

        return view('admin.predios.edit', compact('predio', 'municipios', 'planimetrias', 'propietarios', 'prediosPadre', 'vias', 'materialesVias'));
    }

    public function update(Request $request, Predio $predio)
    {
        $request->validate([
            // Identificación y Jerarquía
            // Se asegura que un predio no pueda ser su propio padre
            'inmueble_padre_id' => 'nullable|integer|exists:predios,id|not_in:' . $predio->id,
            'propiedad_horizontal' => 'nullable|boolean',
            'numero_unidad' => 'nullable|string|max:20|required_with:inmueble_padre_id',
            // La regla 'unique' debe ignorar el registro actual al actualizar
            'codigo_catastral' => 'required|string|max:255|unique:predios,codigo_catastral,' . $predio->id,
            'numero_matricula_folio' => 'nullable|string|max:255',
            'numero_plano' => 'nullable|string|max:50',

            // Ubicación
            'manzano' => 'nullable|string|max:20',
            'lote' => 'nullable|string|max:20',
            'provincia' => 'nullable|string|max:255',
            'centro_poblado' => 'nullable|string|max:255',
            'zona' => 'nullable|string|max:255',

            // Superficies y Medidas
            'sup_levantamiento' => 'nullable|numeric|min:0',
            'sup_testimonio' => 'nullable|numeric|min:0',
            'sup_construida' => 'nullable|numeric|min:0',
            'sup_afectada' => 'nullable|numeric|min:0',
            'sup_util' => 'nullable|numeric|min:0',
            'frente_principal' => 'nullable|numeric|min:0',

            // Coordenadas
            'coordenadas_text' => 'nullable|json',

            // Servicios y Características
            'agua_potable' => 'nullable|boolean',
            'energia_electrica' => 'nullable|boolean',
            'alcantarillado' => 'nullable|boolean',
            'alumbrado_publico' => 'nullable|boolean',
            'gas_domiciliario' => 'nullable|boolean',
            'material_via' => 'nullable|string|max:255',
            'forma_lote' => 'nullable|string|in:Regular,Irregular',
            'material_via' => 'nullable|string|max:255',
            'id_material_via' => 'nullable|integer|exists:materiales_vias,id', // Valida contra la tabla
            'via_id' => 'nullable|integer|exists:vias,id', // Valida contra la tabla


            // Fotografías
            'fotografia_uno' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_dos' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_tres' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_cuatro' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_cinco' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // Colindantes
            'colindante_norte' => 'nullable|string|max:255',
            'colindante_sur' => 'nullable|string|max:255',
            'colindante_este' => 'nullable|string|max:255',
            'colindante_oeste' => 'nullable|string|max:255',

            // Relaciones
            'planimetria_id' => 'required|integer|exists:planimetrias,id',
            'propietarios' => 'required|array|min:1',
            'propietarios.*' => 'integer|exists:propietarios,id',
        ]);

        try {
            DB::beginTransaction();

            // 1. Prepara los datos del predio
            $data = $request->except(['propietarios', 'coordenadas_text', '_token', '_method', 'fotografia_uno', 'fotografia_dos', 'fotografia_tres', 'fotografia_cuatro', 'fotografia_cinco']);

            // Convertir checkboxes a booleanos
            $data['propiedad_horizontal'] = $request->has('propiedad_horizontal');
            $data['agua_potable'] = $request->has('agua_potable');
            $data['energia_electrica'] = $request->has('energia_electrica');
            $data['alcantarillado'] = $request->has('alcantarillado');
            $data['alumbrado_publico'] = $request->has('alumbrado_publico');
            $data['gas_domiciliario'] = $request->has('gas_domiciliario');

            if ($request->filled('forma_lote')) {
                $data['forma_lote'] = ($request->input('forma_lote') === 'Regular');
            }

            // 2. Maneja las cargas de archivos de fotografías (si se sube un nuevo archivo, reemplaza el anterior)
            $photoFields = ['fotografia_uno', 'fotografia_dos', 'fotografia_tres', 'fotografia_cuatro', 'fotografia_cinco'];
            foreach ($photoFields as $field) {
                if ($request->hasFile($field)) {
                    // Opcional: Eliminar la foto anterior si existe
                    //if ($predio->$field) { Storage::disk('public')->delete($predio->$field); }
                    $path = $request->file($field)->store('predio_fotos', 'public');
                    $data[$field] = $path;
                }
            }

            // 3. Convierte el texto de coordenadas JSON a un objeto Polygon
            if ($request->filled('coordenadas_text')) {
                $points = [];
                $coordenadasArray = json_decode($request->input('coordenadas_text'), true);
                foreach ($coordenadasArray as $coord) {
                    if (is_numeric($coord['este']) && is_numeric($coord['norte'])) {
                        $points[] = Point::make((float)$coord['este'], (float)$coord['norte']);
                    }
                }

                if (count($points) > 2) {
                    if (!$this->pointsAreEqual($points[0], end($points))) {
                        $points[] = $points[0];
                    }
                    $lineString = new LineString($points);
                    $data['coordenadas'] = new Polygon([$lineString], 4326);
                }
            }

            // 4. Actualiza el predio
            $predio->update($data);

            // 5. Sincroniza los propietarios en la tabla pivote
            if ($request->has('propietarios')) {
                // sync() elimina las relaciones antiguas y añade las nuevas. Es ideal para un formulario de edición.
                $predio->propietarios()->sync($request->propietarios);
                // Nota: Si necesitas mantener el historial, la lógica aquí sería más compleja.
            }

            DB::commit();
            return redirect()->route('admin.predios.index')->with('success', 'Predio actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ocurrió un error al actualizar el predio: ' . $e->getMessage()]);
        }
    }

    public function destroy(Predio $predio)
    {
        $predio->delete(); // Soft delete
        return redirect()->route('admin.predios.index')->with('success', 'Predio desactivado exitosamente.');
    }

    public function procesarPlano(Request $request)
    {
        $request->validate(['documento' => 'required|file|mimes:pdf,jpg,png,jpeg|max:10240']);

        $file = $request->file('documento');
        $mimeType = $file->getMimeType();
        $fileContents = $file->get();

        if ($mimeType === 'application/pdf') {
            // Lógica de conversión de PDF a JPG
            $tempPdfPath = $file->getRealPath();
            $tempDir = storage_path('app/public/ocr_temp');
            File::ensureDirectoryExists($tempDir);
            $tempJpgPath = $tempDir . '/' . uniqid() . '.jpg';
            try {
                (new Pdf($tempPdfPath))->resolution(300)->save($tempJpgPath);
                if (!File::exists($tempJpgPath) || File::size($tempJpgPath) === 0) throw new \Exception("Conversión de PDF a imagen falló.");
                $fileContents = file_get_contents($tempJpgPath);
                $mimeType = 'image/jpeg';
                unlink($tempJpgPath);
            } catch (\Exception $e) {
                return response()->json(['error' => 'No se pudo convertir el PDF.', 'details' => $e->getMessage()], 500);
            }
        }

        $base64File = base64_encode($fileContents);
        $prompt = $this->getPromptParaPlanoCatastral();
        $apiKey = env('OPENROUTER_API_KEY');

        if (!$apiKey) return response()->json(['error' => 'API Key de OpenRouter no configurada.'], 500);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json'
        ])->timeout(120)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'google/gemma-3-12b-it:free',
            'messages' => [['role' => 'user', 'content' => [['type' => 'text', 'text' => $prompt], ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$base64File}"]]]]],
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->successful()) {
            $contentString = $response->json('choices.0.message.content');
            if (!$contentString) {
                return response()->json(['error' => 'La API no devolvió contenido útil.'], 500);
            }

            $jsonString = $contentString;
            if (strpos(trim($jsonString), '```json') === 0) {
                $jsonString = str_replace('```json', '', $jsonString);
                $jsonString = str_replace('```', '', $jsonString);
            }

            $datosExtraidos = json_decode(trim($jsonString), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'La IA no devolvió un JSON válido.', 'raw_response' => $contentString], 400);
            }

            if (!empty($datosExtraidos['propietarios'])) {
                $propietarioIdsEncontrados = [];
                foreach ($datosExtraidos['propietarios'] as $propietarioData) {
                    if (!empty($propietarioData['ci'])) {
                        // Limpiar el CI (ej. "232064 LP" -> "232064")
                        $ciNumber = preg_replace('/[^0-9]/', '', $propietarioData['ci']);

                        $persona = Persona::where('carnet', $ciNumber)->first();
                        // Si se encuentra la persona y tiene un registro de propietario asociado
                        if ($persona && $persona->propietario) {
                            $propietarioIdsEncontrados[] = $persona->propietario->id;
                        }
                    }
                }

                if (!empty($propietarioIdsEncontrados)) {
                    // Añadimos el array de IDs a la respuesta
                    $datosExtraidos['propietario_ids_encontrados'] = $propietarioIdsEncontrados;
                }
            }

            return response()->json($datosExtraidos);
        } else {
            return response()->json(['error' => 'Error en la comunicación con la API.', 'details' => $response->body()], $response->status());
        }
    }


    private function getPromptParaPlanoCatastral(): string
    {
        return <<<PROMPT
Eres un asistente experto en análisis de planos catastrales de Bolivia. Analiza la imagen y extrae la información clave. Responde ÚNICAMENTE en formato JSON válido. Si un dato no se encuentra, usa `null`.

Reglas:
1.  **codigo_catastral**: Concatena Distrito, Manzano y Lote. Si "Distrito" no existe, usa "01" por defecto. Para el ""codigo_catastral" que los numeros de Distrito, Manzano y Lote no se encuentren separados por guiones ni ningun elemento separador, que se encuentren unidos, lado a lado.
2.  **coordenadas_utm**: Extrae **TODOS Y CADA UNO** de los puntos de la tabla de coordenadas. No omitas ninguno. Devuelve un array de objetos, cada uno con "este" y "norte".
3.  **frente_principal**: Busca en el plano del lote un número que tenga una flecha apuntando hacia él. Es la medida del frente. Si no es claro, pon `null`.
4.  **servicios_basicos**: Marca como `true` si la 'X' está en la casilla del servicio, `false` si no.
5.  **propietarios**: Busca TODOS los propietarios listados. Devuelve un array de objetos, donde cada objeto contiene el "nombre" y el "ci" de un propietario. Si solo hay uno, el array contendrá un solo objeto.
6.  **manzano**: El numero del manzano se encuentra debajo del texto "CODIGO CATASTRAL". Para encontrar el numero del manzano busca el texto "MANZANO:" y seguido de este texto se encuentra el numero del manzano.
7.  **lote**:  El numero del lote se encuentra debajo del texto "CODIGO CATASTRAL". Para encontrar el numero del lote busca el texto "LOTE:" y seguido de este texto se encuentra el numero del lote.


El formato JSON debe ser:
{
  "numero_plano": "CAQUI 000374",
  "propietarios": [
    {
      "nombre": "TORIBIO SANTIAGO ALAVI MAMANI",
      "ci": "232064 LP"
    }
  ],
  "ubicacion": {
    "departamento": "LA PAZ",
    "provincia": "PACAJES",
    "municipio": "CAQUIAVIRI",
    "centro_poblado": "ACHIRI",
    "zona": null
  },
  "identificacion": {
    "distrito": null,
    "manzano": null,
    "lote": null
  },
  "superficies": {
    "segun_levantamiento": 207.97,
    "segun_testimonio": null,
    "construida": null,
    "afectada": null,
    "util": null
  },
  "frente_principal": 7.05,
  "colindantes": {
    "norte": "LOTE 16 Y CALLEJON",
    "sur": "CALLE NINOCA Y LOTE 21",
    "este": "CALLEJON Y LOTE 17, 18, 19, 20 Y 21",
    "oeste": "LOTE 16 Y 23"
  },
  "servicios_basicos": {
    "agua_potable": false,
    "energia_electrica": true,
    "alcantarillado": false,
    "alumbrado_publico": false,
    "gas_domiciliario": false
  },
  "material_via": "Tierra",
  "forma_lote": "Irregular",
  "coordenadas_utm": []
}
PROMPT;
    }

    public function buscar(Request $request)
    {
        $request->validate(['codigo_catastral' => 'required|string|max:255']);

        $codigo = $request->input('codigo_catastral');

        // CAMBIO: Se elimina ST_Envelope para obtener la geometría real del predio.
        // Lo nombramos 'geom_geojson' para mayor claridad.
        $resultado = DB::table('predios')
            ->where('codigo_catastral', $codigo)
            ->whereNull('deleted_at')
            ->select(DB::raw('ST_AsGeoJSON(ST_Transform(coordenadas, 4326)) as geom_geojson'))
            ->first();

        if (!$resultado || !$resultado->geom_geojson) {
            return response()->json(['error' => 'Código Catastral no encontrado.'], 404);
        }

        // Devolvemos la geometría real en lugar del bbox.
        return response()->json(['geometry' => json_decode($resultado->geom_geojson)]);
    }
}
