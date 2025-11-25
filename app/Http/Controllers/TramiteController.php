<?php

namespace App\Http\Controllers;

use App\Services\PredictionService;

use App\Models\Predio;
use App\Models\Tramite;
use App\Models\TramiteTipo;
use App\Models\TramiteEstado;
use App\Models\Persona;
use App\Models\Propietario;
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
    public function show(Tramite $tramite, PredictionService $predictionService)
    {
        // Cargar todas las relaciones necesarias para la vista de detalles
        $tramite->load(['predio.propietarios.persona', 'solicitante', 'tipo.requisitos', 'estado', 'documentos.requisito', 'documentos.estado']);
        $estados_disponibles = TramiteEstado::orderBy('id')->get();

        // Llamar al servicio para obtener las predicciones
        $predictions = $predictionService->getPredictions($tramite);

        return view('admin.tramites.show', compact('tramite', 'estados_disponibles', 'predictions'));
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

            // confirmar los cambios
            DB::commit();

            return redirect()->back()->with('success', 'Documento subido exitosamente.');
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
            'estado_id' => 'required|exists:tramite_estados,id',
            'fecha_inspeccion' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        $estadoNuevo = TramiteEstado::find($request->estado_id);

        if ($tramite->tramite_tipo_id == 1 && $estadoNuevo->nombre == 'APROBADO') {

            // Si el trámite es una aprobación de plano y se está aprobando,
            // actualizamos el predio principal.
            $predio = $tramite->predio;
            if ($predio) {
                $predio->plano_aprobado = true;
                $predio->save();
            }
        }

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

    public function generarCertificacionTecnica(Tramite $tramite)
    {
        $tramite->load(['solicitante', 'predio.via', 'predio.propietarios']);
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
                $viewName = 'admin.tramites.certificaciones.fusion_lotes'; 
                $fileName = "certificacion-fusion-{$tramite->predio->codigo_catastral}.pdf";
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
            'parrafo_uno' => 'required|string|max:1000',
            'parrafo_dos_negrita' => 'required|string|max:500',
        ]);

        // 2. Cargar los datos del trámite
        $tramite->load(['solicitante', 'predio']);
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
            'input_parrafo_1' => $validated['parrafo_uno'],
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
        
        // Obtenemos todos los propietarios para los selectores
        $propietarios_lista = Propietario::with('persona')->where('estado', true)->get();

        return view('admin.tramites.certificaciones.division-form', compact('tramite', 'propietarios_lista'));
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
            'incisos' => 'required|array|min:2', // Debe tener al menos 2 lotes resultantes
            'incisos.*.manzano' => 'required|string|max:100',
            'incisos.*.lote' => 'required|string|max:100',
            'incisos.*.superficie' => 'required|numeric|min:0',
            'incisos.*.propietario_id' => 'required|integer|exists:propietarios,id',
            'incisos.*.lote_nuevo' => 'required|string|max:100',
            'incisos.*.superficie_legal_porcentaje' => 'required|numeric|min:0|max:100',
            'incisos.*.superficie_util_porcentaje' => 'required|numeric|min:0|max:100',
            'incisos.*.col_norte' => 'required|string',
            'incisos.*.col_sur' => 'required|string',
            'incisos.*.col_este' => 'required|string',
            'incisos.*.col_oeste' => 'required|string',
        ]);

        // Cargar relaciones
        $tramite->load('predio.propietarios.persona', 'predio.via', 'predio.provincia');

        // Procesar los incisos para obtener los nombres de los propietarios
        $incisosData = [];
        foreach ($request->incisos as $inciso) {
            $propietario = Propietario::with('persona')->find($inciso['propietario_id']);
            $inciso['propietario_nombre'] = $propietario->persona->nombre_completo;
            $inciso['propietario_ci'] = $propietario->persona->carnet . ' ' . $propietario->persona->expedido;
            $incisosData[] = $inciso;
        }

        $datos = [
            'tramite' => $tramite,
            'propietarios' => $tramite->predio->propietarios,
            'predio' => $tramite->predio,
            'testimonio_numero' => $request->testimonio_numero,
            'testimonio_fecha_formato' => Carbon::parse($request->testimonio_fecha)->locale('es')->isoFormat('D \d\e MMMM \d\e\l Y'),
            'superficie_total' => $request->superficie_total,
            'incisos' => $incisosData, // El array de incisos con los datos del form
            'fecha_actual_larga' => Carbon::now()->locale('es')->isoFormat('D \d\e MMMM \d\e\l Y'),
            'img_escudo' => $this->getImageAsBase64(public_path('img/escudo_bolivia.png')),
            'img_logo' => $this->getImageAsBase64(public_path('img/logo_catastro_ayoayo.png')),
        ];

        // Cargar la vista del PDF
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.tramites.certificaciones.pdf.division-pdf', $datos);
        $pdf->setPaper('letter', 'portrait');
        $fileName = 'Resolucion_Division_' . $tramite->id . '.pdf';
        
        return $pdf->stream($fileName);
    }
}
