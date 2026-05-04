<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use App\Models\Planimetria;
use App\Models\Predio;
use App\Models\Propietario;
use App\Models\Persona;
use App\Models\Via;
use App\Models\MaterialVia;
use App\Models\Provincia;
use App\Models\CentroPoblado;
use App\Models\PropietarioPredioEstado;
use App\Models\Orientacion;
use App\Models\TipoColindante;
use App\Models\PredioColindancia;
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
            $status = $request->input('status', 'active'); // 'active' por defecto
            // Consulta base con relaciones necesarias
            $estadoActual = PropietarioPredioEstado::where('nombre', 'Propietario Actual')->firstOrFail();
            $query = Predio::with([
                'municipio',
                'planimetria',
                'propietarios' => function($q) use ($estadoActual) {
                    $q->wherePivot('estado_id', $estadoActual->id);
                },
                'propietarios.persona',
                'provincia',
                'centroPoblado'
            ]);

            if ($status == 'inactive') {
                $query->onlyTrashed();
            }

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
                    if ($predio->trashed()) {
                        // Si está eliminado, muestra solo "Reactivar"
                        $restoreUrl = route('admin.predios.restore', $predio->id);
                        return '<form action="' . $restoreUrl . '" method="POST" class="d-inline form-restore">
                                    ' . csrf_field() . '
                                    <button type="submit" class="btn btn-sm btn-info" title="Reactivar"><i class="fas fa-undo"></i></button>
                                </form>';
                    }
                    $editUrl = route('admin.predios.edit', $predio);
                    $deleteUrl = route('admin.predios.destroy', $predio);

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

    /**
     * Reactiva un predio desactivado (soft delete).
     */
    public function restore($id)
    {
        $predio = Predio::withTrashed()->findOrFail($id);
        $predio->restore();

        return redirect()->route('admin.predios.index')->with('success', 'Predio reactivado exitosamente.');
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

        $provincias = Provincia::all();
        $centrosPoblados = CentroPoblado::all();
        $orientaciones = Orientacion::all();
        $tiposColindante = TipoColindante::all();

        return view('admin.predios.create', compact('predio', 'municipios', 'planimetrias', 'propietarios', 'prediosPadre', 'vias', 'materialesVias', 'provincias', 'centrosPoblados', 'orientaciones', 'tiposColindante'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // Identificación y Jerarquía
            'inmueble_padre_id' => 'nullable|integer|exists:predios,id',
            'propiedad_horizontal' => 'nullable|boolean',
            'numero_unidad' => 'nullable|string|max:20|required_with:inmueble_padre_id',
            'numero_matricula_folio' => 'required|string|max:255',
            'numero_plano' => 'nullable|string|max:50',

            // Ubicación
            'manzano' => 'nullable|string|max:20',
            'lote' => 'nullable|string|max:20',
            'provincia_id' => 'nullable|integer|exists:provincias,id',
            'centro_poblado_id' => 'nullable|integer|exists:centro_poblados,id',
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
            'id_material_via' => 'nullable|integer|exists:materiales_via,id', // Valida contra la tabla
            'via_id' => 'nullable|integer|exists:vias,id', // Valida contra la tabla

            // Fotografías
            'fotografia_uno' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_dos' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_tres' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_cuatro' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_cinco' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // Colindantes (Array)
            'colindancias' => 'nullable|array',
            'colindancias.*.orientacion_id' => 'required|exists:orientaciones,id',
            'colindancias.*.tipo_colindante_id' => 'required|exists:tipo_colindantes,id',
            'colindancias.*.via_id' => 'nullable|exists:vias,id',
            'colindancias.*.nombre_o_numero' => 'nullable|string|max:255',

            // Relaciones
            'planimetria_id' => 'required|integer|exists:planimetrias,id',
            'propietarios' => 'required|array|min:1',
            'propietarios.*' => 'integer|exists:propietarios,id',
        ]);

        try {
            DB::beginTransaction();

            // 1. Prepara los datos del predio
            $data = $request->except(['propietarios', 'colindancias', 'coordenadas_text', 'codigo_catastral', '_token', '_method', 'fotografia_uno', 'fotografia_dos', 'fotografia_tres', 'fotografia_cuatro', 'fotografia_cinco']);

            $data['municipio_id'] = Auth::user()->hasRole('Admin-Municipal') ? Auth::user()->municipio_id : $request->municipio_id;

            // Formatear manzano y lote con ceros iniciales si es necesario
            $data['manzano'] = $this->formatManzanoLote($request->input('manzano'));
            $data['lote'] = $this->formatManzanoLote($request->input('lote'));

            $data['propiedad_horizontal'] = $request->has('propiedad_horizontal');
            $data['agua_potable'] = $request->has('agua_potable');
            $data['energia_electrica'] = $request->has('energia_electrica');
            $data['alcantarillado'] = $request->has('alcantarillado');
            $data['alumbrado_publico'] = $request->has('alumbrado_publico');
            $data['gas_domiciliario'] = $request->has('gas_domiciliario');

            if ($request->filled('forma_lote')) {
                $data['forma_lote'] = ($request->input('forma_lote') === 'Regular');
            }

            if ($request->hasFile('fotografias')) {
                $photoFields = ['fotografia_uno', 'fotografia_dos', 'fotografia_tres', 'fotografia_cuatro', 'fotografia_cinco'];
                foreach ($request->file('fotografias') as $key => $file) {
                    if ($key < count($photoFields)) {
                        $path = $file->store('predio_fotos', 'public');
                        $data[$photoFields[$key]] = $path;
                    }
                }
            }

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
            $predio = Predio::create($data);

            // Guardar Colindancias
            if ($request->has('colindancias')) {
                foreach ($request->colindancias as $colindanciaData) {
                    $predio->colindancias()->create([
                        'orientacion_id' => $colindanciaData['orientacion_id'],
                        'tipo_colindante_id' => $colindanciaData['tipo_colindante_id'],
                        'via_id' => $colindanciaData['via_id'] ?? null,
                        'nombre_o_numero' => $colindanciaData['nombre_o_numero'] ?? null,
                    ]);
                }
            }

            if ($request->has('propietarios')) {
                $estadoActual = PropietarioPredioEstado::where('nombre', 'Propietario Actual')->firstOrFail();
                $predio->propietarios()->attach($request->propietarios, [
                    'estado_id' => $estadoActual->id,
                    'fecha_inicio' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::commit();
            return redirect()->route('admin.predios.index')->with('success', 'Predio registrado exitosamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            // Código 23505 = Unique violation en PostgreSQL
            if ($e->getCode() == '23505') {
                return back()->withInput()->withErrors(['error' => 'No se pudo registrar: El Código Catastral resultante (01-Manzano-Lote) ya existe en el sistema. Verifique que la combinación de Manzano y Lote no esté duplicada.']);
            }
            return back()->withInput()->withErrors(['error' => 'Error de base de datos: ' . $e->getMessage()]);
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
        // Cargar solo los propietarios actuales y colindancias
        $estadoActual = PropietarioPredioEstado::where('nombre', 'Propietario Actual')->firstOrFail();
        $predio->load([
            'propietarios' => function ($query) use ($estadoActual) {
                $query->wherePivot('estado_id', $estadoActual->id);
            },
            'colindancias.orientacion',
            'colindancias.tipoColindante',
            'colindancias.via'
        ]);

        $municipios = Municipio::all();
        $planimetrias = Planimetria::all();
        $propietarios = Propietario::with('persona')->where('estado', true)->get();
        $prediosPadre = Predio::where('propiedad_horizontal', true)->where('id', '!=', $predio->id)->get();
        $vias = Via::all();
        $materialesVias = MaterialVia::all();

        $provincias = Provincia::all();
        $centrosPoblados = CentroPoblado::all();
        $orientaciones = Orientacion::all();
        $tiposColindante = TipoColindante::all();

        return view('admin.predios.edit', compact('predio', 'municipios', 'planimetrias', 'propietarios', 'prediosPadre', 'vias', 'materialesVias', 'provincias', 'centrosPoblados', 'orientaciones', 'tiposColindante'));
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
            'numero_matricula_folio' => 'required|string|max:255',
            'numero_plano' => 'nullable|string|max:50',

            // Ubicación
            'manzano' => 'nullable|string|max:20',
            'lote' => 'nullable|string|max:20',
            'provincia_id' => 'nullable|integer|exists:provincias,id',
            'centro_poblado_id' => 'nullable|integer|exists:centro_poblados,id',
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
            'id_material_via' => 'nullable|integer|exists:materiales_via,id', // Valida contra la tabla
            'via_id' => 'nullable|integer|exists:vias,id', // Valida contra la tabla


            // Fotografías
            'fotografia_uno' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_dos' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_tres' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_cuatro' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'fotografia_cinco' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // Colindantes (Array)
            'colindancias' => 'nullable|array',
            'colindancias.*.orientacion_id' => 'required|exists:orientaciones,id',
            'colindancias.*.tipo_colindante_id' => 'required|exists:tipo_colindantes,id',
            'colindancias.*.via_id' => 'nullable|exists:vias,id',
            'colindancias.*.nombre_o_numero' => 'nullable|string|max:255',

            // Relaciones
            'planimetria_id' => 'required|integer|exists:planimetrias,id',
            'propietarios' => 'required|array|min:1',
            'propietarios.*' => 'integer|exists:propietarios,id',
        ]);

        try {
            DB::beginTransaction();

            // 1. Prepara los datos del predio
            $data = $request->except(['propietarios', 'colindancias', 'coordenadas_text', 'codigo_catastral', '_token', '_method', 'fotografia_uno', 'fotografia_dos', 'fotografia_tres', 'fotografia_cuatro', 'fotografia_cinco']);

            // Formatear manzano y lote con ceros iniciales si es necesario
            $data['manzano'] = $this->formatManzanoLote($request->input('manzano'));
            $data['lote'] = $this->formatManzanoLote($request->input('lote'));

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

            // Guardar Colindancias (Borrar anteriores y crear nuevas)
            if ($request->has('colindancias')) {
                $predio->colindancias()->delete();
                foreach ($request->colindancias as $colindanciaData) {
                    $predio->colindancias()->create([
                        'orientacion_id' => $colindanciaData['orientacion_id'],
                        'tipo_colindante_id' => $colindanciaData['tipo_colindante_id'],
                        'via_id' => $colindanciaData['via_id'] ?? null,
                        'nombre_o_numero' => $colindanciaData['nombre_o_numero'] ?? null,
                    ]);
                }
            }

            // 5. Sincroniza los propietarios con lógica de historial
            if ($request->has('propietarios')) {
                $estadoActual = PropietarioPredioEstado::where('nombre', 'Propietario Actual')->firstOrFail();
                $estadoEx = PropietarioPredioEstado::where('nombre', 'Ex-Propietario')->firstOrFail();

                $nuevosPropietariosIds = $request->propietarios;

                // Obtener IDs actuales activos
                // Nota: Usamos newPivotStatement o una query directa para evitar cargar modelos pesados si solo queremos IDs,
                // pero usando la relación Eloquent es más limpio si no son muchos.
                $idsActuales = $predio->propietarios()
                    ->wherePivot('estado_id', $estadoActual->id)
                    ->pluck('propietarios.id')
                    ->toArray();

                // Identificar cambios
                $aEliminar = array_diff($idsActuales, $nuevosPropietariosIds); // Estaban y ya no están
                $aAgregar = array_diff($nuevosPropietariosIds, $idsActuales); // No estaban y ahora están

                // 1. Marcar como Ex-Propietarios a los que se quitan
                if (!empty($aEliminar)) {
                    $predio->propietarios()->newPivotStatement()
                        ->where('predio_id', $predio->id)
                        ->whereIn('propietario_id', $aEliminar)
                        ->where('estado_id', $estadoActual->id)
                        ->update([
                            'estado_id' => $estadoEx->id,
                            'fecha_fin' => now(),
                            'updated_at' => now()
                        ]);
                }

                // 2. Agregar nuevos como Propietario Actual
                if (!empty($aAgregar)) {
                    $attachData = [];
                    foreach ($aAgregar as $id) {
                        $attachData[$id] = [
                            'estado_id' => $estadoActual->id,
                            'fecha_inicio' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    $predio->propietarios()->attach($attachData);
                }
            }

            DB::commit();
            return redirect()->route('admin.predios.index')->with('success', 'Predio actualizado exitosamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            // Código 23505 = Unique violation en PostgreSQL
            if ($e->getCode() == '23505') {
                return back()->withInput()->withErrors(['error' => 'No se pudo actualizar: El Código Catastral resultante (Distrito-Manzano-Lote) ya existe en otro predio. Verifique los datos.']);
            }
            return back()->withInput()->withErrors(['error' => 'Error de base de datos: ' . $e->getMessage()]);
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
        
        $predio = Predio::with(['municipio', 'propietarios.persona'])
            ->select(
                'predios.*',
                DB::raw('ST_AsGeoJSON(ST_Transform(coordenadas, 4326)) as geom_geojson')
            )
            ->where('codigo_catastral', $codigo)
            ->first();

        if (!$predio || !$predio->geom_geojson) {
            return response()->json(['error' => 'Código Catastral no encontrado.'], 404);
        }

        return response()->json([
            'geometry' => json_decode($predio->geom_geojson),
            'data' => [
                'codigo_catastral' => $predio->codigo_catastral,
                'propietarios' => $predio->propietarios->map(fn($p) => $p->persona->nombre_completo)->join(', '),
                'municipio' => $predio->municipio->nombre ?? '',
                'zona' => $predio->zona,
                'manzano' => $predio->manzano,
                'lote' => $predio->lote,
                'sup_levantamiento' => $predio->sup_levantamiento,
                'sup_testimonio' => $predio->sup_testimonio,
                'sup_construida' => $predio->sup_construida,
            ]
        ]);
    }

    /**
     * Devuelve los propietarios de un predio específico en formato JSON.
     */
    /**
     * Devuelve el siguiente número de lote para un manzano dado.
     */
    public function getNextLoteNumber(Request $request)
    {
        $request->validate([
            'manzano' => 'required|string|max:20',
        ]);

        $manzanoInput = $request->query('manzano');
        // Asegurar que buscamos con el formato correcto (ej. '05' en vez de '5')
        $manzano = $this->formatManzanoLote($manzanoInput);

        $municipio_id = Auth::user()->municipio_id;

        // Si es super-admin y manda municipio_id, usarlo (opcional)
        if (Auth::user()->hasRole('Super-Admin') && $request->has('municipio_id')) {
            $municipio_id = $request->query('municipio_id');
        }

        // Buscar predios en ese manzano y municipio
        // Intentamos convertir 'lote' a número para sacar el máximo correctamente
        // (Si 'lote' es alfanumérico puro, esto podría fallar o dar 0 en algunos DBs,
        //  pero para números guardados como string suele funcionar con cast).
        $maxLote = Predio::where('municipio_id', $municipio_id)
            ->where('manzano', $manzano)
            ->selectRaw('MAX(CAST(NULLIF(regexp_replace(lote, \'[^0-9]\', \'\', \'g\'), \'\') AS INTEGER)) as max_lote')
            ->value('max_lote');

        $nextLote = ($maxLote) ? $maxLote + 1 : 1;

        return response()->json(['next_lote' => $nextLote]);
    }

    public function getPropietariosAjax(Predio $predio)
    {
        $estadoActual = PropietarioPredioEstado::where('nombre', 'Propietario Actual')->firstOrFail();

        $predio->load(['propietarios' => function ($query) use ($estadoActual) {
            $query->where('propietarios.estado', true) // Estado del propietario (si está activo en el sistema)
                  ->wherePivot('estado_id', $estadoActual->id) // Estado de la relación con el predio
                  ->with('persona');
        }]);

        $propietariosData = $predio->propietarios->map(function ($propietario) {
            if ($propietario->persona) {
                return [
                    'id' => $propietario->persona->id,
                    'text' => $propietario->persona->nombre_completo . ' (' . $propietario->persona->carnet . ')'
                ];
            }
            return null;
        })->filter();

        return response()->json($propietariosData);
    }

    /**
     * Formatea un valor de manzano o lote para que tenga al menos 2 dígitos si es numérico y menor a 10.
     */
    private function formatManzanoLote(?string $value): ?string
    {
        if (is_numeric($value) && (int)$value >= 1 && (int)$value <= 9) {
            return str_pad((int)$value, 2, '0', STR_PAD_LEFT);
        }
        return $value;
    }
}
