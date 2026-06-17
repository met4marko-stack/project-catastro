<?php

namespace App\Http\Controllers;

use App\Services\PredictionService;
use App\Jobs\ValidarDocumentoIA;

use App\Models\Predio;
use App\Models\Tramite;
use App\Models\TramiteTipo;
use App\Models\TramiteEstado;
use App\Models\Persona;
use App\Models\Propietario;
use App\Models\Municipio;
use App\Models\Planimetria;
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
use Yajra\DataTables\Facades\DataTables;
use App\Models\DocumentoEstado;
use App\Models\TramiteDocumento;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Requisito;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Clickbar\Magellan\Data\Geometries\MultiPolygon;
use Clickbar\Magellan\Data\Geometries\Polygon;
use Clickbar\Magellan\Data\Geometries\Point;
use Clickbar\Magellan\Data\Geometries\LineString;


class TramiteController extends Controller
{
    /**
     * Muestra la lista de trámites.
     */
    public function index(Request $request)
    {
        // Obtenemos el parámetro de la URL, por defecto es 'todos'
        $estadoFilter = $request->query('estado', 'todos');

        if ($request->ajax()) {
            $user = Auth::user();

            // Si es archivado, buscamos en los eliminados. Si no, en los normales.
            $query = ($estadoFilter === 'archivado') ? Tramite::onlyTrashed() : Tramite::query();

            $query->with([
                'predio' => function ($query) {
                    $query->withTrashed();
                },
                'solicitante',
                'tipo',
                'estado'
            ]);

            if ($user->hasRole('Admin-Municipal')) {
                $query->where('municipio_id', $user->municipio_id);
            }

            // Aplicar el filtro según el estado si no es 'todos' ni 'archivado'
            if ($estadoFilter !== 'todos' && $estadoFilter !== 'archivado') {
                $query->whereHas('estado', function ($q) use ($estadoFilter) {
                    $q->where('nombre', strtoupper($estadoFilter));
                });
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->editColumn('predio', fn($tramite) => $tramite->predio->codigo_catastral ?? 'N/A')
                ->editColumn('solicitante', fn($tramite) => $tramite->solicitante->nombre_completo ?? 'N/A')
                ->editColumn('tipo', fn($tramite) => $tramite->tipo->nombre ?? 'N/A')
                ->editColumn('estado', function ($tramite) {
                    $color = '#6c757d';
                    if ($tramite->estado) {
                        switch (strtoupper($tramite->estado->nombre)) {
                            case 'APROBADO':
                                $color = '#28a745';
                                break;
                            case 'OBSERVADO':
                            case 'PARALIZADO':
                                $color = '#ffc107';
                                break;
                            case 'RECHAZADO':
                                $color = '#dc3545';
                                break;
                                // Puedes añadir más colores aquí según prefieras
                        }
                    }
                    return '<span class="badge" style="background-color:' . $color . '; color:white;">' . ($tramite->estado->nombre ?? 'N/A') . '</span>';
                })
                ->editColumn('fecha_ingreso', fn($tramite) => $tramite->fecha_ingreso->format('d/m/Y'))
                ->addColumn('acciones', function ($tramite) {

                    $showUrl = route('admin.tramites.show', $tramite);
                    $actions = '<div class="btn-group">';

                    $actions .= '<a href="' . $showUrl . '" class="btn btn-sm btn-info" title="Ver Detalles"><i class="fas fa-eye"></i></a>';
                    if ($tramite->trashed()) {
                        $restoreUrl = route('admin.tramites.restore', $tramite->id);
                        $actions .= '<form action="' . $restoreUrl . '" method="POST" class="form-restore" style="display:inline;">'
                            . csrf_field()
                            . '<button type="submit" class="btn btn-sm btn-success" title="Reactivar"><i class="fas fa-undo"></i></button>'
                            . '</form>';
                    } else {
                        $editUrl = route('admin.tramites.edit', $tramite);
                        $actions .= '<a href="' . $editUrl . '" class="btn btn-sm btn-warning" title="Editar Trámite"><i class="fas fa-edit"></i></a>';
                        $deleteUrl = route('admin.tramites.destroy', $tramite);
                        $actions .= '<form action="' . $deleteUrl . '" method="POST" class="form-delete" style="display:inline;">'
                            . csrf_field() . method_field('DELETE')
                            . '<button type="submit" class="btn btn-sm btn-danger" title="Archivar"><i class="fas fa-archive"></i></button>'
                            . '</form>';
                    }
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['estado', 'acciones'])
                ->toJson();
        }

        // Enviamos el estado a la vista para personalizar el título
        return view('admin.tramites.index', compact('estadoFilter'));
    }
    /*public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            $status = $request->query('status', 'active');

            $query = ($status === 'inactive') ? Tramite::onlyTrashed() : Tramite::query();

            $query->with([
                'predio' => function ($query) {
                    $query->withTrashed();
                }, 
                'solicitante', 
                'tipo', 
                'estado'
            ]);

            if ($user->hasRole('Admin-Municipal')) {
                $query->where('municipio_id', $user->municipio_id);
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->editColumn('predio', fn($tramite) => $tramite->predio->codigo_catastral ?? 'N/A')
                ->editColumn('solicitante', fn($tramite) => $tramite->solicitante->nombre_completo ?? 'N/A')
                ->editColumn('tipo', fn($tramite) => $tramite->tipo->nombre ?? 'N/A')
                ->editColumn('estado', function ($tramite) {
                    $color = '#6c757d';
                    if ($tramite->estado) {
                        switch (strtoupper($tramite->estado->nombre)) {
                            case 'APROBADO':
                                $color = '#28a745';
                                break;
                            case 'OBSERVADO':
                            case 'PARALIZADO':
                                $color = '#ffc107';
                                break;
                            case 'RECHAZADO':
                                $color = '#dc3545';
                                break;
                        }
                    }
                    return '<span class="badge" style="background-color:' . $color . '; color:white;">' . ($tramite->estado->nombre ?? 'N/A') . '</span>';
                })
                ->editColumn('fecha_ingreso', fn($tramite) => $tramite->fecha_ingreso->format('d/m/Y'))
                ->addColumn('acciones', function ($tramite) {

                    $showUrl = route('admin.tramites.show', $tramite);
                    $actions = '<div class="btn-group">';

                    $actions .= '<a href="' . $showUrl . '" class="btn btn-sm btn-info" title="Ver Detalles"><i class="fas fa-eye"></i></a>';
                    if ($tramite->trashed()) {
                        $restoreUrl = route('admin.tramites.restore', $tramite->id);
                        $actions .= '<form action="' . $restoreUrl . '" method="POST" class="form-restore" style="display:inline;">'
                            . csrf_field()
                            . '<button type="submit" class="btn btn-sm btn-success" title="Reactivar"><i class="fas fa-undo"></i></button>'
                            . '</form>';
                    } else {
                        $editUrl = route('admin.tramites.edit', $tramite);
                        $actions .= '<a href="' . $editUrl . '" class="btn btn-sm btn-warning" title="Editar Trámite"><i class="fas fa-edit"></i></a>';
                        $deleteUrl = route('admin.tramites.destroy', $tramite);
                        $actions .= '<form action="' . $deleteUrl . '" method="POST" class="form-delete" style="display:inline;">'
                            . csrf_field() . method_field('DELETE')
                            . '<button type="submit" class="btn btn-sm btn-danger" title="Archivar"><i class="fas fa-archive"></i></button>'
                            . '</form>';
                    }
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['estado', 'acciones'])
                ->toJson();
        }
        return view('admin.tramites.index');
    }*/

    /**
     * Muestra el formulario para crear un nuevo trámite.
     */
    public function create()
    {
        $user = Auth::user();

        $tipos_de_tramite = TramiteTipo::orderBy('nombre')->get();
        $queryPredios = Predio::query();
        //$personas = Persona::orderBy('nombre')->get();

        if ($user->hasRole('Admin-Municipal')) {
            $queryPredios->where('municipio_id', $user->municipio_id);
        }

        $predios = $queryPredios->orderBy('codigo_catastral')->get();
        $expedidoOptions = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD', 'QR'];

        $tramite = new Tramite();

        return view('admin.tramites.create', compact('tramite', 'tipos_de_tramite', 'predios', 'expedidoOptions'));
    }

    /**
     * Almacena un nuevo trámite en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'predio_id' => 'required|exists:predios,id',
            'solicitante_id' => 'required|exists:personas,id',
            'tramite_tipo_id' => 'required|exists:tramite_tipos,id',
            'fecha_ingreso' => 'required|date',
            'hoja_ruta' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $tramite = null;

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $predio = Predio::findOrFail($request->predio_id);

            // Buscar el estado inicial "INGRESADO"
            $estadoInicial = TramiteEstado::where('nombre', 'INGRESADO')->firstOrFail();

            do {
                // Genera un código alfanumérico de 8 caracteres y lo pone en mayúsculas
                $codigoAleatorio = Str::upper(Str::random(8));
            } while (Tramite::where('codigo_acceso', $codigoAleatorio)->exists());

            $tramite = Tramite::create([
                'predio_id' => $predio->id,
                'municipio_id' => $predio->municipio_id,
                'usuario_id' => $user->id,
                'solicitante_id' => $request->solicitante_id,
                'tramite_tipo_id' => $request->tramite_tipo_id,
                'estado_id' => $estadoInicial->id,
                'fecha_ingreso' => $request->fecha_ingreso,
                'hoja_ruta' => $request->hoja_ruta,
                'codigo_acceso' => $codigoAleatorio,
                'observaciones' => $request->observaciones,
            ]);

            DB::commit();

            return redirect()->route('admin.tramites.show', $tramite)
                ->with('success', 'Trámite registrado exitosamente.')
                ->with('codigo_generado', $codigoAleatorio);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ocurrió un error al registrar el trámite: ' . $e->getMessage()]);
        }
    }

    /**
     * Muestra los detalles de un trámite específico.
     */
    public function show(Tramite $tramite, PredictionService $predictionService)
    {
        $tramite->load([
            'predio' => function ($query) {
                $query->withTrashed();
            },
            'predio.propietarios' => function ($q) {
                $estadoActual = \App\Models\PropietarioPredioEstado::where('nombre', 'Propietario Actual')->firstOrFail();
                $q->wherePivot('estado_id', $estadoActual->id);
            },
            'predio.propietarios.persona',
            'solicitante',
            'tipo.requisitos',
            'estado',
            'estadoAnterior',
            'documentos.requisito',
            'documentos.estado'
        ]);

        $predictions = $predictionService->getPredictions($tramite);

        // Definimos la secuencia lógica de estados
        $secuencia = [
            'INGRESADO'  => 'REVISION',
            'REVISION'   => 'INSPECCION',
            'INSPECCION' => 'APROBADO',
            'APROBADO'   => 'ENTREGADO'
        ];

        $estadoActualNombre = strtoupper($tramite->estado->nombre);
        $siguienteEstadoNombre = $secuencia[$estadoActualNombre] ?? null;

        $siguienteEstado = null;
        if ($siguienteEstadoNombre) {
            $siguienteEstado = TramiteEstado::where('nombre', $siguienteEstadoNombre)->first();
        }

        return view('admin.tramites.show', compact('tramite', 'predictions', 'siguienteEstado'));
    }

    /**
     * Muestra el formulario para editar un trámite existente.
     */
    public function edit(Tramite $tramite)
    {
        $user = Auth::user();

        $tipos_de_tramite = TramiteTipo::orderBy('nombre')->get();

        $queryPredios = Predio::query();

        if ($user->hasRole('Admin-Municipal')) {
            $queryPredios->where('municipio_id', $user->municipio_id);
        }

        $predios = $queryPredios->orderBy('codigo_catastral')->get();

        $personas = Persona::orderBy('nombre')->get();

        $expedidoOptions = ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BE', 'PD', 'QR'];

        return view('admin.tramites.edit', compact('tramite', 'tipos_de_tramite', 'predios', 'personas', 'expedidoOptions'));
    }

    /**
     * Actualiza un trámite en la base de datos.
     */
    public function update(Request $request, Tramite $tramite)
    {
        $request->validate([
            'predio_id' => 'required|exists:predios,id',
            'solicitante_id' => 'required|exists:personas,id',
            'tramite_tipo_id' => 'required|exists:tramite_tipos,id',
            'fecha_ingreso' => 'required|date',
            'hoja_ruta' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $predio = Predio::findOrFail($request->predio_id);

            $tramite->update([
                'predio_id' => $predio->id,
                'municipio_id' => $predio->municipio_id,
                'solicitante_id' => $request->solicitante_id,
                'tramite_tipo_id' => $request->tramite_tipo_id,
                'fecha_ingreso' => $request->fecha_ingreso,
                'hoja_ruta' => $request->hoja_ruta,
                'observaciones' => $request->observaciones,
            ]);

            DB::commit();

            return redirect()->route('admin.tramites.index')->with('success', 'Trámite actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Ocurrió un error al actualizar el trámite: ' . $e->getMessage()]);
        }
    }

    /**
     * Añade o reemplaza un documento digitalizado para un trámite.
     */
    public function addDocumento(Request $request, Tramite $tramite)
    {
        $request->validate([
            'documento' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'requisito_id' => 'required|exists:requisitos,id',
        ]);

        $file = $request->file('documento');
        $requisito = Requisito::find($request->requisito_id);
        $estadoRecibido = DocumentoEstado::where('nombre', 'RECIBIDO')->firstOrFail();

        // Definir el disco, la ruta y el nombre del archivo
        $disk = Storage::disk('documentos_locales');
        $filePath = "tramite_{$tramite->id}/requisitos";
        $fileName = "requisito_{$request->requisito_id}_"
            . Str::slug($requisito->nombre) . "_"
            . now()->format('YmdHis') . "."
            . $file->getClientOriginalExtension();

        $newPath = null; // Variable para rastrear el archivo nuevo

        DB::beginTransaction(); // Iniciar la transacción

        try {
            // Buscar el documento existente
            $documentoExistente = TramiteDocumento::where('tramite_id', $tramite->id)
                ->where('requisito_id', $request->requisito_id)->first();

            // Si existía un archivo anterior, borrarlo del disco
            if ($documentoExistente && $documentoExistente->ruta_archivo) {
                $disk->delete($documentoExistente->ruta_archivo);
            }

            $newPath = $file->storeAs($filePath, $fileName, 'documentos_locales');

            TramiteDocumento::updateOrCreate(
                ['tramite_id' => $tramite->id, 'requisito_id' => $request->requisito_id],
                [
                    'ruta_archivo' => $newPath,
                    'nombre_original' => $file->getClientOriginalName(),
                    'user_id' => Auth::id(),

                    'estado_id' => $estadoRecibido->id,

                    'observaciones' => null,
                ]
            );

            $documentoGuardado = TramiteDocumento::updateOrCreate(
                ['tramite_id' => $tramite->id, 'requisito_id' => $request->requisito_id],
                [
                    'ruta_archivo' => $newPath,
                    'nombre_original' => $file->getClientOriginalName(),
                    'user_id' => Auth::id(),
                    'estado_id' => $estadoRecibido->id,
                ]
            );

            // Asignamos la observación directamente para evitar el problema de $fillable
            $documentoGuardado->observaciones = "Procesando validación automática...";
            $documentoGuardado->save();

            // confirmar los cambios
            DB::commit();

            // ----------------------------------------------------
            // PREPARAR CONTEXTO Y ENVIAR A LA IA EN SEGUNDO PLANO
            // ----------------------------------------------------
            $predio = $tramite->predio;
            // Tomamos el primer propietario actual (puedes ajustar esta lógica si hay múltiples)
            $propietario = $predio->propietarios()->wherePivot('estado_id', \App\Models\PropietarioPredioEstado::where('nombre', 'Propietario Actual')->first()->id)->first();

            $contexto = [
                'matricula' => $predio->numero_matricula_folio ?? '',
                'manzano'   => $predio->manzano ?? '',
                'lote'      => $predio->lote ?? '',
                'nombre'    => $propietario ? $propietario->persona->nombre_completo : '',
                'ci'        => $propietario ? $propietario->persona->carnet : '',
                'superficie' => $predio->sup_levantamiento ?? '',
                'nombre_solicitante' => $tramite->solicitante->nombre_completo, 
                'ci_solicitante' => $tramite->solicitante->carnet
            ];

            ValidarDocumentoIA::dispatch($documentoGuardado->id, $contexto);

            // Si la petición es por AJAX (nuestro nuevo formulario), devolvemos JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'documento_id' => $documentoGuardado->id,
                    'observaciones' => $documentoGuardado->observaciones,
                    'estado_id' => $estadoRecibido->id,
                    'estado_nombre' => $estadoRecibido->nombre,
                    'estado_color' => $estadoRecibido->color_ui ?? '#6c757d',
                    'url_ver' => route('admin.tramites.verDocumento', $documentoGuardado->id)
                ]);
            }

            return redirect()->back()->with('success', 'Documento subido exitosamente y enviado a validación automática .');
        } catch (\Exception $e) {
            // Si algo falló (el guardado del archivo o el guardado en BD)...
            DB::rollBack(); // Deshacer cualquier cambio en la base de datos

            if ($newPath && $disk->exists($newPath)) {
                $disk->delete($newPath);
            }

            return back()->withErrors(['error' => 'No se pudo subir el archivo: ' . $e->getMessage()]);
        }
    }

    /**
     * Actualiza el estado general y las fechas de un trámite.
     */
    public function updateStatus(Request $request, Tramite $tramite)
    {
        $request->validate([
            'accion'            => 'required|in:avanzar,observar,paralizar,subsanar',
            'estado_destino_id' => 'nullable|exists:tramite_estados,id',
            'fecha_inspeccion'  => 'nullable|date',
            'observaciones'     => 'nullable|string',
        ]);

        $accion = $request->accion;
        $estadoActualNombre = strtoupper($tramite->estado->nombre);

        // Procesar la acción
        if ($accion === 'avanzar') {
            $tramite->estado_id = $request->estado_destino_id;
            $tramite->fecha_paralizado = null; // Limpiar si venía de algún lado raro
        } elseif ($accion === 'observar') {
            $estadoDestino = TramiteEstado::where('nombre', 'OBSERVADO')->firstOrFail();
            $tramite->estado_anterior_id = $tramite->estado_id;
            $tramite->estado_id = $estadoDestino->id;
        } elseif ($accion === 'paralizar') {
            $estadoDestino = TramiteEstado::where('nombre', 'PARALIZADO')->firstOrFail();
            $tramite->estado_anterior_id = $tramite->estado_id;
            $tramite->estado_id = $estadoDestino->id;
            $tramite->fecha_paralizado = now();
        } elseif ($accion === 'subsanar') {
            // Regresamos al estado anterior
            $tramite->estado_id = $tramite->estado_anterior_id;
            $tramite->estado_anterior_id = null;
            $tramite->fecha_paralizado = null;
        }

        // Actualizar fecha de inspección si se envió
        if ($request->filled('fecha_inspeccion')) {
            $tramite->fecha_inspeccion = $request->fecha_inspeccion;
        }

        // Añadir observación con etiqueta de la acción
        if ($request->filled('observaciones')) {
            $etiqueta = strtoupper($accion);
            $tramite->observaciones = $tramite->observaciones . "\n- " . now()->format('d/m/Y') . " [$etiqueta]: " . $request->observaciones;
        }

        // Lógica especial de plano aprobado
        if ($tramite->tramite_tipo_id == 1 && TramiteEstado::find($tramite->estado_id)->nombre == 'APROBADO') {
            if ($tramite->predio) {
                $tramite->predio->plano_aprobado = true;
                $tramite->predio->save();
            }
        }

        $tramite->save();

        return back()->with('success', 'El estado del trámite ha sido actualizado correctamente.');
    }

    /**
     * Actualiza el estado y las observaciones de un documento específico.
     */
    public function updateDocumentoStatus(Request $request, Tramite $tramite, TramiteDocumento $documento)
    {
        // Asegurarse de que el documento pertenece al trámite por seguridad
        if ($documento->tramite_id !== $tramite->id) {
            abort(404);
        }

        $request->validate([
            'estado_id' => 'required|exists:documento_estados,id',
            'observaciones' => 'nullable|string',
        ]);

        $documento->estado_id = $request->estado_id;
        $documento->observaciones = $request->observaciones;
        $documento->save();

        return back()->with('success', 'El estado del documento ha sido actualizado.');
    }

    public function generarCertificacionTecnica(Tramite $tramite)
    {
        // 1. Verificar si ya existe un certificado guardado
        if ($tramite->ruta_certificado && Storage::disk('public')->exists($tramite->ruta_certificado)) {
            // Si el certificado ya existe, lo descargamos directamente.
            return Storage::disk('public')->download($tramite->ruta_certificado);
        }

        // 2. Cargar las relaciones necesarias, incluyendo predios borrados (withTrashed)
        $tramite->load([
            'solicitante',
            'predio' => function ($query) {
                $query->withTrashed();
            },
            'predio.via',
            'predio.propietarios'
        ]);

        // Si el predio está borrado y no se pudo cargar, se retorna un error.
        if (!$tramite->predio) {
            return back()->withErrors('El predio asociado a este trámite no fue encontrado.');
        }

        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $fecha_actual = [
            'mes' => $meses[now()->month - 1],
            'ano' => now()->year
        ];

        // --- Decidir qué plantilla y nombre de archivo usar ---
        $viewName = ''; // Variable para guardar el nombre de la vista
        $fileName = "certificado-{$tramite->predio->codigo_catastral}.pdf"; // Nombre base

        // Usamos un switch para seleccionar la vista correcta
        switch ($tramite->tramite_tipo_id) {

            case 1: // ID 1 = Aprobación de Plano
                $viewName = 'admin.tramites.certificaciones.aprobacion_plano';
                $fileName = "certificacion-aprobacion-{$tramite->predio->codigo_catastral}.pdf";
                break;

            case 2: // ID 2 = División de Lotes
                return redirect()->route('admin.tramites.divisionForm', $tramite);
                break;

            case 3: //  ID 3 = Fusión de Lotes
                return redirect()->route('admin.tramites.fusionForm', $tramite);
                break;

            case 5: //  ID 5 = Línea y Nivel
                return redirect()->route('admin.tramites.lineaNivelForm', $tramite);
            case 8: //  ID 8 = "Certificación Técnica Varia"
                // Este tipo de trámite necesita un formulario previo.
                return redirect()->route('admin.tramites.certificacionVariaForm', $tramite);

            default:
                // Si el tipo de trámite no tiene un certificado definido, regresa con un error.
                return redirect()->back()->withErrors('Este tipo de trámite no tiene una certificación generable.');
        }

        $pdf = Pdf::loadView($viewName, compact('tramite', 'fecha_actual'));
        $pdf->setPaper('letter'); // Tamaño carta

        // Guardar el PDF y actualizar la ruta_certificado del trámite
        $filePath = 'certificados_tramite/' . $tramite->id . '/' . $fileName;
        Storage::disk('public')->put($filePath, $pdf->output());

        $tramite->ruta_certificado = $filePath;
        $tramite->save();

        return $pdf->stream($fileName);
    }

    /**
     * Desactiva un trámite (borrado lógico) y actualiza su estado a "Archivado".
     */
    public function destroy(Tramite $tramite)
    {
        try {
            // 1. Buscar el ID del estado "ARCHIVADO"
            $estadoArchivado = TramiteEstado::where('nombre', 'ARCHIVADO')->firstOrFail();

            // 2. Asignar el nuevo estado
            $tramite->estado_id = $estadoArchivado->id;
            $tramite->save();

            // 3. Ejecutar el Soft Delete (archivado)
            $tramite->delete();

            return redirect()->route('admin.tramites.index')->with('success', 'Trámite archivado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('admin.tramites.index')->withErrors(['error' => 'No se pudo archivar el trámite.']);
        }
    }
    /**
     * Reactiva un trámite archivado.
     */
    public function restore($id)
    {
        $tramite = Tramite::withTrashed()->findOrFail($id);
        $tramite->restore();
        return redirect()->route('admin.tramites.index')->with('success', 'Trámite reactivado exitosamente.');
    }

    public function verDocumento($documentoId)
    {
        $documento = TramiteDocumento::findOrFail($documentoId);

        // Opcional: Autorizar si el usuario puede ver este documento
        // $this->authorize('view', $documento->tramite); 

        $path = $documento->ruta_archivo;
        $disk = Storage::disk('documentos_locales');

        // Verificar si el archivo existe en nuestro disco
        if (!$disk->exists($path)) {
            abort(404, 'Archivo no encontrado.');
        }

        // Obtener el tipo de archivo (ej. 'application/pdf', 'image/jpeg')
        $mimeType = $disk->mimeType($path);

        // Devolver el archivo al navegador
        // "response()" permite al navegador mostrar el PDF o la imagen
        // en lugar de forzar la descarga.
        return $disk->response($path, $documento->nombre_original, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $documento->nombre_original . '"',
        ]);
    }

    // --- FUNCIÓN: MOSTRAR EL FORMULARIO ---
    public function showCertificacionVariaForm(Tramite $tramite)
    {
        // Verificamos que el trámite esté aprobado para poder generar el certificado
        if (strtoupper($tramite->estado->nombre) !== 'APROBADO' && strtoupper($tramite->estado->nombre) !== 'ENTREGADO') {
            return redirect()->route('admin.tramites.show', $tramite)->withErrors('El trámite debe estar APROBADO para generar esta certificación.');
        }

        // Simplemente devolvemos la nueva vista de formulario
        return view('admin.tramites.certificaciones.certificacion_varia_form', compact('tramite'));
    }

    // --- FUNCIÓN: GENERAR EL PDF CON DATOS MANUALES ---
    public function generateCertificacionVaria(Request $request, Tramite $tramite)
    {
        // 1. Validar los datos que el usuario escribió en el formulario
        $validated = $request->validate([
            'titulo_certificado' => 'required|string|max:255',
            // 'parrafo_uno' ya no se valida porque es automático
            'parrafo_dos_negrita' => 'required|string|max:500',
        ]);

        // 2. Cargar los datos del trámite
        $tramite->load(['solicitante', 'predio']);

        // --- Lógica de generación automática del Párrafo 1 ---
        Carbon::setLocale('es');
        $fechaSolicitud = Carbon::parse($tramite->fecha_ingreso)->isoFormat('D \d\e MMMM \d\e\l Y');
        $nombreSolicitante = $tramite->solicitante->nombre_completo;
        $ciSolicitante = $tramite->solicitante->carnet . ' ' . $tramite->solicitante->expedido;

        $parrafoUnoAutomatico = "Que en atención a la solicitud presentada en fecha {$fechaSolicitud}, por el Sr. {$nombreSolicitante} con C.I. {$ciSolicitante}, dirigida al Sr. Honorable Alcalde Municipal del Gobierno Autónomo Municipal de Ayo Ayo.";
        // -----------------------------------------------------

        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $fecha_actual = [
            'dia' => now()->day, // Añadimos el día
            'mes' => $meses[now()->month - 1],
            'ano' => now()->year
        ];

        // 3. Lógica para el contador (Requisito 2)
        $estadoAprobado = \App\Models\TramiteEstado::where('nombre', 'APROBADO')->first();

        // Contamos cuántos trámites de este TIPO (ID 5) fueron APROBADOS este AÑO
        $count = Tramite::where('tramite_tipo_id', $tramite->tramite_tipo_id) // ej. 5
            ->where('estado_id', $estadoAprobado->id)
            ->whereYear('updated_at', now()->year)
            ->count();

        // Asignamos el número actual (si es el primero del año, será 1)
        // Usamos str_pad para rellenar con ceros hasta 6 dígitos (ej. 000001)
        $numero_certificado = str_pad($count, 6, "0", STR_PAD_LEFT);
        $codigo_certificado = "GAM-AYOAYO - $numero_certificado/" . $fecha_actual['ano'];

        // 4. Preparar todos los datos para la vista del PDF
        $data = [
            'tramite' => $tramite,
            'fecha_actual' => $fecha_actual,
            'codigo_certificado' => $codigo_certificado,
            'input_titulo' => $validated['titulo_certificado'],
            'input_parrafo_1' => $parrafoUnoAutomatico, // Usamos el generado
            'input_parrafo_2_negrita' => $validated['parrafo_dos_negrita'],
        ];

        // 5. Generar el PDF
        $pdf = Pdf::loadView('admin.tramites.certificaciones.certificacion_varia_template', $data);
        $pdf->setPaper('letter');
        $fileName = "certificacion-{$tramite->hoja_ruta}.pdf";

        return $pdf->stream($fileName);
    }

    /**
     * Muestra el formulario para los datos manuales de Línea y Nivel.
     */
    public function showLineaNivelForm(Tramite $tramite)
    {
        // Verificamos que el trámite esté aprobado para poder generar el certificado
        if (strtoupper($tramite->estado->nombre) !== 'APROBADO' && strtoupper($tramite->estado->nombre) !== 'ENTREGADO') {
            return redirect()->route('admin.tramites.show', $tramite)->withErrors('El trámite debe estar APROBADO para generar esta certificación.');
        }

        $tramite->load('solicitante', 'predio');

        // Texto predeterminado para el párrafo 2
        $defaultParrafoDos = "Que el solicitante acredita su interés legal presentando en calidad de prueba: Testimonio Nº... de fecha...; el citado predio se encuentra registrado en DDRR. Bajo la matricula Nº... (Fotocopia simple y vigente); Plano de Lote Aprobado en Original y Fotocopia; Boleta de pago de Impuestos, etc.";

        return view('admin.tramites.certificaciones.linea-nivel-form', compact('tramite', 'defaultParrafoDos'));
    }

    /**
     * Genera el PDF final de Línea y Nivel.
     */
    public function generateLineaNivel(Request $request, Tramite $tramite)
    {
        $request->validate(['parrafo_dos' => 'required|string']);

        // Cargar todas las relaciones necesarias para el PDF
        $tramite->load(
            'solicitante',
            'predio.propietarios.persona', // Carga todos los propietarios
            'predio.planimetria',
            'predio.provincia',
            'predio.via'
        );

        $datos = [
            'tramite' => $tramite,
            'predio' => $tramite->predio,
            'propietarios' => $tramite->predio->propietarios,
            'parrafo_dos' => $request->parrafo_dos,
            'distrito' => '01', // Valor estático
            'centroPoblado' => 'TOLAR', // Valor estático
            'fecha_actual' => $this->getFechaActual(),
            'img_escudo' => $this->getImageAsBase64(public_path('img/escudo_bolivia.png')),
            'img_logo' => $this->getImageAsBase64(public_path('img/logo_catastro_ayoayo.png')),
        ];

        // Cargar la vista del PDF
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.tramites.certificaciones.linea-nivel-pdf', $datos);

        // Opcional: configurar el papel
        $pdf->setPaper('letter', 'portrait');

        // Generar un nombre de archivo
        $fileName = 'Cert_Linea_Nivel_' . $tramite->id . '.pdf';

        // Mostrar el PDF en el navegador
        return $pdf->stream($fileName);
    }
    
    // ... [tus funciones de updateStatus, certificacionVariaForm, etc.] ...

    // --- FUNCIONES HELPER ---

    /**
     * Obtiene la fecha actual en formato español.
     */
    private function getFechaActual()
    {
        Carbon::setLocale('es');
        $fecha = Carbon::now();
        return [
            'dia' => $fecha->day,
            'mes' => $fecha->monthName,
            'ano' => $fecha->year,
        ];
    }

    /**
     * Convierte una imagen a Base64 para el PDF.
     */
    private function getImageAsBase64($path)
    {
        if (!file_exists($path)) {
            return null; // O una imagen placeholder en base64
        }
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    /**
     * Muestra el formulario para los datos manuales de División/Fusión.
     */
    public function showDivisionForm(Tramite $tramite)
    {
        // Validar que el trámite esté aprobado
        if (strtoupper($tramite->estado->nombre) !== 'APROBADO' && strtoupper($tramite->estado->nombre) !== 'ENTREGADO') {
            return redirect()->route('admin.tramites.show', $tramite)->withErrors('El trámite debe estar APROBADO para generar esta certificación.');
        }

        $tramite->load('predio.propietarios.persona');

        // Obtenemos los predios disponibles para seleccionar (excluyendo el original y eliminados)
        // Filtramos por el mismo municipio para evitar listas gigantes, y ordenamos por código
        $prediosDisponibles = Predio::where('municipio_id', $tramite->municipio_id)
            ->where('id', '!=', $tramite->predio_id)
            ->orderBy('codigo_catastral', 'desc')
            ->get();

        return view('admin.tramites.certificaciones.division-form', compact('tramite', 'prediosDisponibles'));
    }

    /**
     * Genera el PDF final de División y Partición.
     */
    public function generateDivisionPdf(Request $request, Tramite $tramite)
    {
        $request->validate([
            'testimonio_numero' => 'required|string|max:100',
            'testimonio_fecha' => 'required|date',
            'superficie_total' => 'required|numeric|min:0',
            'incisos' => 'required|array|min:2',
            'incisos.*.predio_id' => 'required|exists:predios,id', // ID del predio seleccionado
            'incisos.*.denominativo' => 'required|string|max:100', // El "LOTE 4-A"
            'incisos.*.lote_nuevo' => 'required|string|max:100', // El número nuevo
            'incisos.*.superficie_legal_porcentaje' => 'required|numeric|min:0|max:100',
            'incisos.*.superficie_util_porcentaje' => 'required|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            // 1. DESACTIVAR EL PREDIO ORIGINAL (Soft Delete)
            // Cargamos el predio original y lo borramos, si no ha sido borrado ya.
            $predioOriginal = $tramite->predio()->withTrashed()->first(); // Cargar con soft-deleted
            if ($predioOriginal && !$predioOriginal->trashed()) {
                $predioOriginal->delete();
            }

            // 2. Preparar datos para el PDF
            $tramite->load(['predio' => function ($query) {
                $query->withTrashed();
            }, 'predio.propietarios.persona', 'predio.via', 'predio.provincia']);

            $incisosData = [];

            foreach ($request->incisos as $incisoInput) {
                // Cargar el predio seleccionado con sus relaciones, incluyendo los borrados
                $predioSeleccionado = Predio::withTrashed()->with(['propietarios.persona'])->find($incisoInput['predio_id']);

                // Construir el array combinando datos del predio + inputs manuales
                $incisosData[] = [
                    // Datos manuales
                    'lote' => $incisoInput['denominativo'], // Ej: "LOTE 4-A"
                    'lote_nuevo' => $incisoInput['lote_nuevo'], // Ej: "24"
                    'superficie_legal_porcentaje' => $incisoInput['superficie_legal_porcentaje'],
                    'superficie_util_porcentaje' => $incisoInput['superficie_util_porcentaje'],

                    // Datos extraídos automáticamente del predio seleccionado
                    'manzano' => $predioSeleccionado->manzano,
                    'superficie' => $predioSeleccionado->sup_levantamiento, // Asumimos sup. levantamiento

                    'col_norte' => $predioSeleccionado->getColindanciaString('NORTE'),
                    'col_sur' => $predioSeleccionado->getColindanciaString('SUR'),
                    'col_este' => $predioSeleccionado->getColindanciaString('ESTE'),
                    'col_oeste' => $predioSeleccionado->getColindanciaString('OESTE'),

                    'propietario_id' => $predioSeleccionado->propietarios->first()->id ?? 0, // Referencia
                    'propietario_nombre' => $predioSeleccionado->propietarios->map(function ($p) {
                        return $p->persona->nombre_completo;
                    })->join(' y '),
                    'propietario_ci' => $predioSeleccionado->propietarios->map(function ($p) {
                        return $p->persona->carnet . ' ' . $p->persona->expedido;
                    })->join(' y '),
                ];
            }

            $datos = [
                'tramite' => $tramite,
                'propietarios' => $tramite->predio->propietarios,
                'predio' => $tramite->predio,
                'testimonio_numero' => $request->testimonio_numero,
                'testimonio_fecha_formato' => Carbon::parse($request->testimonio_fecha)->locale('es')->isoFormat('D \d\e MMMM \d\e\l Y'),
                'superficie_total' => $request->superficie_total,
                'incisos' => $incisosData,
                'fecha_actual_larga' => Carbon::now()->locale('es')->isoFormat('D \d\e MMMM \d\e\l Y'),
                'img_escudo' => $this->getImageAsBase64(public_path('img/escudo_bolivia.png')),
                'img_logo' => $this->getImageAsBase64(public_path('img/logo_catastro_ayoayo.png')),
            ];

            // Cargar la vista del PDF
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.tramites.certificaciones.division-pdf', $datos);
            $pdf->setPaper('letter', 'portrait');
            $fileName = 'Resolucion_Division_' . $tramite->id . '.pdf';

            // Guardar el PDF y actualizar la ruta_certificado del trámite
            $filePath = 'certificados_tramite/' . $tramite->id . '/' . $fileName;
            Storage::disk('public')->put($filePath, $pdf->output());

            $tramite->ruta_certificado = $filePath;
            $tramite->save();

            DB::commit();

            return $pdf->stream($fileName);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Error al generar el certificado de división: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el formulario para Fusión de Lotes.
     */
    public function showFusionForm(Tramite $tramite)
    {
        if (strtoupper($tramite->estado->nombre) !== 'APROBADO' && strtoupper($tramite->estado->nombre) !== 'ENTREGADO') {
            return redirect()->route('admin.tramites.show', $tramite)->withErrors('El trámite debe estar APROBADO para generar esta certificación.');
        }

        $tramite->load('predio.propietarios.persona');

        // Predios disponibles para seleccionar (como anexados o resultante)
        $prediosDisponibles = Predio::where('municipio_id', $tramite->municipio_id)
            ->where('id', '!=', $tramite->predio_id)
            ->orderBy('codigo_catastral', 'desc')
            ->get();

        return view('admin.tramites.certificaciones.fusion-form', compact('tramite', 'prediosDisponibles'));
    }

    /**
     * Genera el PDF final de Fusión y Anexión.
     */
    public function generateFusionPdf(Request $request, Tramite $tramite)
    {
        $request->validate([
            'testimonio_numero' => 'required|string|max:100',
            'testimonio_fecha' => 'required|date',
            'predios_anexados' => 'required|array|min:1', // Al menos un predio adicional
            'predios_anexados.*' => 'exists:predios,id',
            'predio_resultante_id' => 'required|exists:predios,id',
        ]);

        DB::beginTransaction();
        try {
            // 1. Desactivar Predio Original
            // Cargar con withTrashed para asegurar que, si ya está borrado, podamos operar.
            $predioOriginal = $tramite->predio()->withTrashed()->first();
            if ($predioOriginal && !$predioOriginal->trashed()) {
                $predioOriginal->delete();
            }

            // 2. Desactivar Predios Anexados
            foreach ($request->predios_anexados as $id) {
                // Cargar con withTrashed
                $p = Predio::withTrashed()->find($id);
                if ($p && !$p->trashed()) {
                    $p->delete();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Error al procesar la fusión: ' . $e->getMessage());
        }

        // 3. Cargar datos para PDF
        // Recuperar información de los predios borrados para el reporte
        $predioBase = $tramite->predio()->withTrashed()->with('propietarios.persona')->first();

        $prediosAnexados = Predio::withTrashed()
            ->with('propietarios.persona')
            ->whereIn('id', $request->predios_anexados)
            ->get();

        $predioResultante = Predio::with(['propietarios.persona', 'via', 'provincia', 'planimetria'])
            ->find($request->predio_resultante_id);

        $datos = [
            'tramite' => $tramite,
            'predioBase' => $predioBase,
            'prediosAnexados' => $prediosAnexados,
            'predioResultante' => $predioResultante,
            'testimonio_numero' => $request->testimonio_numero,
            'testimonio_fecha_formato' => Carbon::parse($request->testimonio_fecha)->locale('es')->isoFormat('D \d\e MMMM \d\e\l Y'),
            'fecha_actual_larga' => Carbon::now()->locale('es')->isoFormat('D \d\e MMMM \d\e\l Y'),
            'img_escudo' => $this->getImageAsBase64(public_path('img/escudo_bolivia.png')),
            'img_logo' => $this->getImageAsBase64(public_path('img/logo_catastro_ayoayo.png')),
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.tramites.certificaciones.fusion-pdf', $datos);
        $pdf->setPaper('letter', 'portrait');
        $fileName = 'Resolucion_Fusion_' . $tramite->id . '.pdf';

        // Guardar el PDF y actualizar la ruta_certificado del trámite
        $filePath = 'certificados_tramite/' . $tramite->id . '/' . $fileName;
        Storage::disk('public')->put($filePath, $pdf->output());

        $tramite->ruta_certificado = $filePath;
        $tramite->save();

        return $pdf->stream($fileName);
    }

    /**
     * Muestra el formulario para EJECUTAR la división (Crear nuevos predios).
     */
    public function createDivision(Tramite $tramite)
    {
        // Validar que el trámite esté aprobado
        if (strtoupper($tramite->estado->nombre) !== 'APROBADO' && strtoupper($tramite->estado->nombre) !== 'ENTREGADO') {
            return redirect()->route('admin.tramites.show', $tramite)->withErrors('El trámite debe estar APROBADO para ejecutar la división.');
        }

        // Cargar datos necesarios para los formularios de predios (Misma lógica que PredioController@create)
        $municipios = Municipio::all();
        $planimetrias = Planimetria::all();
        $propietarios = Propietario::with('persona')->where('estado', true)->get();
        // Predios padre para PH (excluyendo el actual por si acaso, aunque al dividirse deja de existir)
        $prediosPadre = Predio::where('propiedad_horizontal', true)->where('id', '!=', $tramite->predio_id)->get();
        $vias = Via::all();
        $materialesVias = MaterialVia::all();
        $provincias = Provincia::all();
        $centrosPoblados = CentroPoblado::all();

        // Pasamos el predio original para referencia
        $predioOriginal = $tramite->predio;

        return view('admin.tramites.division.execute', compact(
            'tramite',
            'predioOriginal',
            'municipios',
            'planimetrias',
            'propietarios',
            'prediosPadre',
            'vias',
            'materialesVias',
            'provincias',
            'centrosPoblados'
        ));
    }

    /**
     * Almacena la división ejecutada (crea nuevos predios y desactiva el anterior).
     */
    public function storeDivision(Request $request, Tramite $tramite)
    {
        $request->validate([
            'testimonio_numero' => 'nullable|string|max:100',
            'testimonio_fecha' => 'nullable|date',
            'predios' => 'required|array|min:2', // Al menos 2 predios resultantes
            'predios.*.planimetria_id' => 'required|exists:planimetrias,id',
            'predios.*.propietarios' => 'required|array',
            'predios.*.propietarios.*' => 'exists:propietarios,id',
            'predios.*.codigo_catastral' => 'required|string|distinct',
            'predios.*.numero_matricula_folio' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $lotesGenerados = [];

            // 1. Crear los nuevos predios
            foreach ($request->predios as $index => $predioData) {

                // Determinar el valor del lote: Si hay denominativo (ej. 4-A), se usa ese. Si no, el lote numérico.
                $loteValor = !empty($predioData['denominativo']) ? $predioData['denominativo'] : ($predioData['lote'] ?? null);

                $data = [
                    'municipio_id' => $tramite->municipio_id,
                    'planimetria_id' => $predioData['planimetria_id'],
                    'numero_plano' => $predioData['numero_plano'] ?? null,
                    'numero_matricula_folio' => $predioData['numero_matricula_folio'],
                    'codigo_catastral' => $predioData['codigo_catastral'],
                    'manzano' => $predioData['manzano'] ?? null,
                    'lote' => $loteValor, // Usamos el valor determinado
                    'zona' => $predioData['zona'] ?? null,
                    'provincia_id' => $predioData['provincia_id'] ?? null,
                    'centro_poblado_id' => $predioData['centro_poblado_id'] ?? null,

                    'sup_levantamiento' => $predioData['sup_levantamiento'] ?? 0,
                    'sup_testimonio' => $predioData['sup_testimonio'] ?? 0,
                    'sup_construida' => $predioData['sup_construida'] ?? 0,
                    'sup_afectada' => $predioData['sup_afectada'] ?? 0,
                    'sup_util' => $predioData['sup_util'] ?? 0,

                    'frente_principal' => $predioData['frente_principal'] ?? 0,
                    'id_material_via' => $predioData['id_material_via'] ?? null,
                    'via_id' => $predioData['via_id'] ?? null,
                    'forma_lote' => ($predioData['forma_lote'] ?? '') === 'Regular',

                    'propiedad_horizontal' => isset($predioData['propiedad_horizontal']),
                    'inmueble_padre_id' => $predioData['inmueble_padre_id'] ?? null,
                    'numero_unidad' => $predioData['numero_unidad'] ?? null,

                    'agua_potable' => isset($predioData['agua_potable']),
                    'energia_electrica' => isset($predioData['energia_electrica']),
                    'alcantarillado' => isset($predioData['alcantarillado']),
                    'alumbrado_publico' => isset($predioData['alumbrado_publico']),
                    'gas_domiciliario' => isset($predioData['gas_domiciliario']),
                ];

                // NOTA: Se han removido fotos y coordenadas para este flujo rápido.

                $nuevoPredio = Predio::create($data);

                // Procesar y guardar colindancias normalizadas desde el texto
                $this->procesarColindanciaTexto($nuevoPredio, 'NORTE', $predioData['colindante_norte'] ?? null);
                $this->procesarColindanciaTexto($nuevoPredio, 'SUR', $predioData['colindante_sur'] ?? null);
                $this->procesarColindanciaTexto($nuevoPredio, 'ESTE', $predioData['colindante_este'] ?? null);
                $this->procesarColindanciaTexto($nuevoPredio, 'OESTE', $predioData['colindante_oeste'] ?? null);

                $lotesGenerados[] = $nuevoPredio->codigo_catastral . " (" . $loteValor . ")";

                // Asignar Propietarios
                if (!empty($predioData['propietarios'])) {
                    $estadoActual = PropietarioPredioEstado::where('nombre', 'Propietario Actual')->firstOrFail();
                    $nuevoPredio->propietarios()->attach($predioData['propietarios'], [
                        'estado_id' => $estadoActual->id,
                        'fecha_inicio' => now(),
                    ]);
                }
            }

            // 2. Desactivar el Predio Original
            $predioOriginal = $tramite->predio;
            $predioOriginal->delete();

            $tramite->observaciones .= "\n- División ejecutada el " . now()->format('d/m/Y') . ". Lotes creados: " . implode(", ", $lotesGenerados);
            $tramite->save();

            DB::commit();

            return redirect()->route('admin.tramites.show', $tramite)->with('success', 'División ejecutada correctamente. Nuevos predios creados: ' . count($lotesGenerados));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al ejecutar la división: ' . $e->getMessage()]);
        }
    }

    /**
     * Compara dos objetos Point con una tolerancia para decimales.
     */
    private function pointsAreEqual(Point $a, Point $b, float $epsilon = 1e-9): bool
    {
        $ax = $a->getX();
        $ay = $a->getY();
        $bx = $b->getX();
        $by = $b->getY();

        return (abs($ax - $bx) < $epsilon) && (abs($ay - $by) < $epsilon);
    }

    /**
     * Procesa un texto de colindancia y crea los registros normalizados.
     */
    private function procesarColindanciaTexto(Predio $predio, string $nombreOrientacion, ?string $textoColindante)
    {
        if (empty($textoColindante)) return;

        $orientacion = Orientacion::where('nombre', $nombreOrientacion)->first();
        if (!$orientacion) return;

        $tipos = TipoColindante::pluck('id', 'nombre');
        $viasDb = Via::all();

        $partes = preg_split('/\s+y\s+|\s*,\s*|\s+e\s+/i', $textoColindante, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($partes as $parte) {
            $parte = trim($parte);
            $tipoId = $tipos['OTRO'] ?? null;
            if (!$tipoId) $tipoId = TipoColindante::first()->id; // Fallback

            $viaId = null;
            $nombreONumero = $parte;

            $parteUpper = strtoupper($parte);

            if (str_starts_with($parteUpper, 'LOTE')) {
                $tipoId = $tipos['LOTE'];
                $nombreONumero = trim(preg_replace('/^LOTES?\s*/i', '', $parte));
            } elseif (str_contains($parteUpper, 'CALLE') || str_contains($parteUpper, 'AV') || str_contains($parteUpper, 'PASAJE')) {
                $tipoId = $tipos['VIA'];
                $viaEncontrada = $viasDb->first(function ($v) use ($parteUpper) {
                    return str_contains($parteUpper, strtoupper($v->nombre));
                });

                if ($viaEncontrada) {
                    $viaId = $viaEncontrada->id;
                    $nombreONumero = null;
                }
            } elseif (str_contains($parteUpper, 'RIO')) {
                $tipoId = $tipos['RIO'];
            } elseif (str_contains($parteUpper, 'AREA VERDE') || str_contains($parteUpper, 'PLAZA')) {
                $tipoId = $tipos['AREA VERDE'];
            } elseif (str_contains($parteUpper, 'EQUIPAMIENTO')) {
                $tipoId = $tipos['EQUIPAMIENTO'];
            }

            PredioColindancia::create([
                'predio_id' => $predio->id,
                'orientacion_id' => $orientacion->id,
                'tipo_colindante_id' => $tipoId,
                'via_id' => $viaId,
                'nombre_o_numero' => $nombreONumero,
            ]);
        }
    }

    public function checkDocumentoStatus($id)
    {
        $documento = TramiteDocumento::find($id);
        if (!$documento) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        return response()->json([
            'observaciones' => $documento->observaciones,
            // Si ya no dice "Procesando", asumimos que terminó
            'terminado' => !str_contains($documento->observaciones, 'Procesando')
        ]);
    }
}
