<?php

namespace App\Http\Controllers;

use App\Models\Predio;
use App\Models\Tramite;
use App\Models\TramiteTipo;
use App\Models\TramiteEstado;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\DocumentoEstado;
use App\Models\TramiteDocumento;
use Illuminate\Support\Facades\Storage; // para el almacenamiento de los archivos
use Illuminate\Support\Str;
use App\Models\Requisito;
use Barryvdh\DomPDF\Facade\Pdf;

class TramiteController extends Controller
{
    /**
     * Muestra la lista de trámites.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            $status = $request->query('status', 'active');

            $query = ($status === 'inactive') ? Tramite::onlyTrashed() : Tramite::query();

            $query->with(['predio', 'solicitante', 'tipo', 'estado']);

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
    }

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
    public function show(Tramite $tramite)
    {
        // Cargar todas las relaciones necesarias para la vista de detalles
        $tramite->load(['predio.propietarios.persona', 'solicitante', 'tipo.requisitos', 'estado', 'documentos.requisito', 'documentos.estado']);

        // Cargar los posibles estados para el dropdown de cambio de estado
        $estados_disponibles = TramiteEstado::orderBy('nombre')->get();

        return view('admin.tramites.show', compact('tramite', 'estados_disponibles'));
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

        try {
            DB::beginTransaction();

            // 1. Buscar si ya existe un documento para este requisito.
            $documentoExistente = TramiteDocumento::where('tramite_id', $tramite->id)
                ->where('requisito_id', $request->requisito_id)
                ->first();

            $file = $request->file('documento');
            $requisito = Requisito::findOrFail($request->requisito_id);
            $predio = $tramite->predio;

            // 2. Construir la ruta y el nombre del NUEVO archivo.
            $municipioSlug = Str::slug($tramite->municipio->nombre, '_');
            $filePath = "{$municipioSlug}/documentos/tramite_{$tramite->id}";
            $codigoCatastralSanitized = preg_replace('/[^a-zA-Z0-9]/', '', $predio->codigo_catastral);
            $requisitoSlug = Str::slug($requisito->nombre);
            $timestamp = now()->format('Ymd-His');
            $fileName = "{$codigoCatastralSanitized}_{$requisitoSlug}_{$timestamp}." . $file->getClientOriginalExtension();

            // 3. Subir el NUEVO archivo a S3.
            $path = $file->storeAs($filePath, $fileName, 's3');

            // 4. Si existía un documento anterior, eliminar el ARCHIVO ANTIGUO de S3.
            if ($documentoExistente) {
                Storage::disk('s3')->delete($documentoExistente->ruta_archivo);
            }

            $estadoRecibido = DocumentoEstado::where('nombre', 'RECIBIDO')->firstOrFail();

            // 5. Crear o actualizar el registro en la base de datos (gracias a updateOrCreate).
            TramiteDocumento::updateOrCreate(
                [
                    'tramite_id' => $tramite->id,
                    'requisito_id' => $request->requisito_id,
                ],
                [
                    'estado_id' => $estadoRecibido->id,
                    'ruta_archivo' => $path,
                    'nombre_original' => $file->getClientOriginalName(),
                    'observaciones' => null,
                ]
            );

            DB::commit();

            return back()->with('success', 'Documento adjuntado y/o actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'No se pudo subir el archivo: ' . $e->getMessage()]);
        }
    }

    /**
     * Actualiza el estado general y las fechas de un trámite.
     */
    public function updateStatus(Request $request, Tramite $tramite)
    {
        $request->validate([
            'estado_id' => 'required|exists:tramite_estados,id',
            'fecha_inspeccion' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        $estadoNuevo = TramiteEstado::find($request->estado_id);

        $tramite->estado_id = $estadoNuevo->id;
        $tramite->fecha_inspeccion = $request->fecha_inspeccion;

        // Añadir las observaciones del formulario a las existentes
        if ($request->filled('observaciones')) {
            $tramite->observaciones = $tramite->observaciones . "\n- " . now()->format('d/m/Y') . ": " . $request->observaciones;
        }

        // Lógica para el contador de 10 días
        if ($estadoNuevo->nombre === 'PARALIZADO') {
            $tramite->fecha_paralizado = now();
        } else {
            // Si se cambia a cualquier otro estado, se resetea el contador
            $tramite->fecha_paralizado = null;
        }

        $tramite->save();

        return back()->with('success', 'El estado del trámite ha sido actualizado.');
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

    // --- INICIO: NUEVO MÉTODO PARA GENERAR EL PDF ---
    public function generarCertificacionTecnica(Tramite $tramite)
    {
        // 1. Validar que el trámite sea el correcto (Aprobación de Plano de Lote)
        // Puedes hacer esta lógica más robusta si lo necesitas
        if ($tramite->tramite_tipo_id != 7) { // Asumiendo que el ID 1 es "Aprobación de Plano..."
            return redirect()->back()->withErrors('Este tipo de trámite no tiene una certificación técnica de este tipo.');
        }

        // 2. Cargar las relaciones necesarias para tener todos los datos
        $tramite->load(['solicitante', 'predio.via']);

        // 3. Crear un array de meses en español para la fecha
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $fecha_actual = [
            'mes' => $meses[now()->month - 1],
            'ano' => now()->year
        ];

        // 4. Cargar la vista del PDF, pasarle los datos y configurar el tamaño
        $pdf = Pdf::loadView('admin.tramites.certificaciones.aprobacion_plano', compact('tramite', 'fecha_actual'));
        $pdf->setPaper('letter'); // Tamaño carta

        // 5. Generar un nombre de archivo dinámico y descargar el PDF
        $fileName = "certificacion-tecnica-{$tramite->predio->codigo_catastral}.pdf";
        return $pdf->stream($fileName); // .stream() lo muestra en el navegador, .download() lo descarga directamente
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
}
