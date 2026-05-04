@extends('adminlte::page')

@section('title', 'Detalle del Trámite')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><b>Detalle del Trámite: {{ $tramite->hoja_ruta ?? $tramite->id }}</b></h1>
        <a href="{{ route('admin.tramites.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </a>
    </div>
@stop

@section('content')

    {{-- Alerta para mostrar el código de acceso generado --}}
    @if (session('codigo_generado'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-check"></i> ¡Trámite Creado Exitosamente!</h5>
            <p>Por favor, entregue los siguientes datos al solicitante para su consulta pública:</p>
            <ul>
                <li><strong>Hoja de Ruta:</strong> {{ $tramite->hoja_ruta }}</li>
                <li><strong>Código de Acceso:</strong> {{ session('codigo_generado') }}</li>
            </ul>
        </div>
    @endif

    {{-- Mostrar mensajes de éxito o error --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        {{-- Columna Izquierda: Checklist y Documentos --}}
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Checklist de Requisitos y Documentos</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Requisito</th>
                                    <th style="width: 120px;">Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tramite->filtered_requisitos as $requisito)
                                    @php
                                        $documento = $tramite->documentos->firstWhere('requisito_id', $requisito->id);
                                    @endphp
                                    <tr>
                                        <td>{{ $requisito->nombre }}</td>
                                        <td>
                                            @if ($documento)
                                                <span class="badge"
                                                    style="background-color: {{ $documento->estado->color_ui ?? '#6c757d' }}; color: white; font-size: 0.9em;">
                                                    {{ $documento->estado->nombre }}
                                                </span>
                                            @else
                                                <span class="badge badge-secondary"
                                                    style="font-size: 0.9em;">Pendiente</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($documento)
                                                <a href="{{ route('admin.tramites.verDocumento', $documento->id) }}"
                                                    target="_blank" class="btn btn-xs btn-info" title="Ver Documento">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>

                                                {{-- BOTÓN PARA CAMBIAR ESTADO --}}
                                                <button class="btn btn-xs btn-default text-dark btn-change-status"
                                                    data-documento-id="{{ $documento->id }}"
                                                    data-estado-actual-id="{{ $documento->estado_id }}"
                                                    data-observaciones="{{ $documento->observaciones }}"
                                                    title="Cambiar Estado">
                                                    <i class="fas fa-sync-alt"></i> Revisar
                                                </button>
                                            @endif

                                            {{-- Botón para Subir/Reemplazar --}}
                                            <button
                                                class="btn btn-xs {{ $documento ? 'btn-warning' : 'btn-primary' }} btn-upload"
                                                data-requisito-id="{{ $requisito->id }}"
                                                data-requisito-nombre="{{ $requisito->nombre }}"
                                                title="{{ $documento ? 'Reemplazar Documento' : 'Subir Documento' }}">
                                                <i class="fas fa-upload"></i> {{ $documento ? 'Reemplazar' : 'Subir' }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">Este tipo de trámite no tiene requisitos
                                            definidos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna Derecha: Información y Acciones --}}
        <div class="col-md-4">

            {{-- =============================================================== --}}
            {{--  CARD DE ANÁLISIS PREDICTIVO  --}}
            {{-- =============================================================== --}}

            {{-- Usamos la variable $predictions que pasaste desde el controlador --}}
            @if (isset($predictions))

                {{-- El color del card se basa en la predicción --}}
                <div class="card card-{{ $predictions['color_riesgo'] ?? 'secondary' }} card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Análisis Predictivo (Modelo: {{ $predictions['modelo_riesgo'] ?? 'N/A' }})
                        </h3>
                    </div>
                    <div class="card-body">

                        <strong><i class="fas fa-exclamation-triangle mr-1"></i> Nivel de Riesgo Estimado</strong>

                        {{-- Sección de Nivel de Riesgo y Probabilidades --}}
                        <div class="d-flex justify-content-between align-items-center mb-2 mt-2">
                            {{-- La etiqueta principal (Bajo, Medio, Alto) --}}
                            <span class="badge badge-{{ $predictions['color_riesgo'] ?? 'secondary' }}"
                                style="font-size: 1.2em;">
                                {{ $predictions['nivel_riesgo'] ?? 'Indeterminado' }}
                            </span>

                            {{-- Las 3 probabilidades --}}
                            <div class="text-right">
                                <small class="text-danger d-block"><b>Alto:</b>
                                    {{ $predictions['probabilidad_alto'] ?? '?' }}%</small>
                                <small class="text-warning d-block"><b>Medio:</b>
                                    {{ $predictions['probabilidad_medio'] ?? '?' }}%</small>
                                <small class="text-success d-block"><b>Bajo:</b>
                                    {{ $predictions['probabilidad_bajo'] ?? '?' }}%</small>
                            </div>
                        </div>

                        {{-- Barra de Progreso de Probabilidades --}}
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-danger" role="progressbar"
                                style="width: {{ $predictions['probabilidad_alto'] ?? 0 }}%" title="Prob. Alto">
                            </div>
                            <div class="progress-bar bg-warning" role="progressbar"
                                style="width: {{ $predictions['probabilidad_medio'] ?? 0 }}%" title="Prob. Medio">
                            </div>
                            <div class="progress-bar bg-success" role="progressbar"
                                style="width: {{ $predictions['probabilidad_bajo'] ?? 0 }}%" title="Prob. Bajo">
                            </div>
                        </div>

                        <hr>

                        {{-- Factores Identificados --}}
                        <strong><i class="fas fa-search-plus mr-1"></i> Factores Clave Identificados</strong>
                        @if (!empty($predictions['factores_identificados']))
                            <ul class="list-unstyled text-muted small mt-2">
                                @foreach ($predictions['factores_identificados'] as $factor)
                                    <li><i
                                            class="fas fa-check text-{{ $predictions['color_riesgo'] ?? 'secondary' }} mr-1"></i>
                                        {{ $factor }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted small mt-2">No se identificaron factores de riesgo significativos.</p>
                        @endif

                        <hr>

                        {{-- Recomendaciones (Nueva Sección) --}}
                        {{-- Esta parte requiere que también pases la variable $recomendaciones desde tu controlador --}}
                        @if (isset($recomendaciones) && !empty($recomendaciones))
                            <strong><i class="fas fa-tasks mr-1"></i> Recomendaciones del Sistema</strong>
                            <ul class="list-unstyled text-muted small mt-2">
                                @foreach ($recomendaciones as $rec)
                                    <li><i class="fas fa-arrow-right text-primary mr-1"></i> {{ $rec }}</li>
                                @endforeach
                            </ul>
                            <hr>
                        @endif

                        {{-- Estimación de Tiempo --}}
                        <strong><i class="far fa-clock mr-1"></i> Tiempo de Resolución Estimado</strong>
                        <p class="text-muted">
                            Aproximadamente **{{ $predictions['dias_prediccion'] ?? '...' }} días**.
                        </p>
                    </div>
                </div>
            @else
                {{-- Fallback si el servicio de ML no está disponible --}}
                <div class="alert alert-warning">
                    El servicio de predicción no está disponible en este momento.
                </div>
            @endif
            {{-- =============================================================== --}}
            {{-- ▲▲▲ FIN: NUEVO CARD DE ANÁLISIS PREDICTIVO ▲▲▲ --}}
            {{-- =============================================================== --}}

            {{-- Panel de Acciones de Flujo del Trámite --}}
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Flujo del Trámite</h3>
                </div>
                <div class="card-body">
                    @php
                        $estadoStr = strtoupper($tramite->estado->nombre);
                    @endphp

                    {{-- 1. CASO: ESTÁ EN FLUJO NORMAL --}}
                    @if (in_array($estadoStr, ['INGRESADO', 'REVISION', 'INSPECCION', 'APROBADO']))

                        @if ($siguienteEstado)
                            <form action="{{ route('admin.tramites.updateStatus', $tramite) }}" method="POST"
                                class="mb-3">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="accion" value="avanzar">
                                <input type="hidden" name="estado_destino_id" value="{{ $siguienteEstado->id }}">

                                @if ($estadoStr === 'REVISION')
                                    <div class="form-group">
                                        <label>Programar Fecha de Inspección:</label>
                                        <input type="date" name="fecha_inspeccion" class="form-control"
                                            value="{{ optional($tramite->fecha_inspeccion)->format('Y-m-d') }}">
                                    </div>
                                @endif

                                <button type="submit" class="btn btn-primary btn-block btn-lg">
                                    <i class="fas fa-arrow-right"></i> Enviar a {{ $siguienteEstado->nombre }}
                                </button>
                            </form>
                        @endif

                        <hr>
                        <p class="text-muted text-center small">¿Ocurrió algún problema con el trámite?</p>

                        <div class="d-flex justify-content-between">
                            <button class="btn btn-warning flex-fill mr-1" data-toggle="modal"
                                data-target="#modalExcepcion" data-accion="observar">
                                <i class="fas fa-exclamation-triangle"></i> Observar
                            </button>
                            <button class="btn btn-danger flex-fill ml-1" data-toggle="modal"
                                data-target="#modalExcepcion" data-accion="paralizar">
                                <i class="fas fa-hand-paper"></i> Paralizar
                            </button>
                        </div>

                        {{-- 2. CASO: ESTÁ OBSERVADO O PARALIZADO --}}
                    @elseif(in_array($estadoStr, ['OBSERVADO', 'PARALIZADO']))
                        <div class="alert alert-{{ $estadoStr == 'OBSERVADO' ? 'warning' : 'danger' }}">
                            <h5><i class="icon fas fa-info"></i> Trámite {{ ucfirst(strtolower($estadoStr)) }}</h5>
                            Vino desde: <strong>{{ $tramite->estadoAnterior->nombre ?? 'Desconocido' }}</strong>
                        </div>

                        <form action="{{ route('admin.tramites.updateStatus', $tramite) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="accion" value="subsanar">

                            <div class="form-group">
                                <label>Observaciones de la subsanación (Opcional):</label>
                                <textarea name="observaciones" class="form-control" rows="2" placeholder="Detalle cómo se resolvió..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-check-circle"></i> Marcar como Subsanado y devolver a
                                {{ $tramite->estadoAnterior->nombre ?? 'Flujo Normal' }}
                            </button>
                        </form>

                        {{-- 3. CASO: FINALIZADOS --}}
                    @elseif(in_array($estadoStr, ['ENTREGADO', 'ARCHIVADO']))
                        <div class="alert alert-info text-center mb-0">
                            Este trámite ya se encuentra <strong>{{ $estadoStr }}</strong> y ha finalizado su flujo.
                        </div>
                    @endif
                </div>
            </div>

            {{-- MODAL PARA OBSERVAR / PARALIZAR --}}
            <div class="modal fade" id="modalExcepcion" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form action="{{ route('admin.tramites.updateStatus', $tramite) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="accion" id="inputAccionExcepcion" value="">
                            <div class="modal-header">
                                <h5 class="modal-title" id="tituloModalExcepcion">Reportar Excepción</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                        aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Motivo / Observaciones (*)</label>
                                    <textarea name="observaciones" class="form-control" rows="3" required
                                        placeholder="Explique detalladamente el motivo..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary" id="btnSubmitExcepcion">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Card de información general --}}
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Información General</h3>
                </div>
                <div class="card-body">
                    <strong><i class="fas fa-file-alt mr-1"></i> Tipo</strong>
                    <p class="text-muted">{{ $tramite->tipo->nombre }}</p>
                    <hr>
                    <strong><i class="fas fa-house-user mr-1"></i> Predio</strong>
                    <p class="text-muted">{{ $tramite->predio->codigo_catastral }}</p>
                    <hr>
                    <strong><i class="fas fa-user-tie mr-1"></i> Solicitante</strong>
                    <p class="text-muted">
                        {{ $tramite->solicitante->nombre_completo }}
                        @if ($tramite->es_realizado_por_apoderado)
                            <span class="badge badge-warning ml-2">Apoderado</span>
                        @else
                            <span class="badge badge-success ml-2">Propietario</span>
                        @endif
                    </p>
                    <hr>
                    <strong><i class="fas fa-calendar-alt mr-1"></i> Ingreso</strong>
                    <p class="text-muted">{{ $tramite->fecha_ingreso->format('d/m/Y') }}</p>

                    @if (strtoupper($tramite->estado->nombre) == 'APROBADO' || strtoupper($tramite->estado->nombre) == 'ENTREGADO')
                        <hr>
                        {{-- --- CAMBIO AQUÍ --- --}}
                        <a href="{{ route('admin.tramites.generarCertificacion', $tramite) }}" target="_blank"
                            class="btn btn-success btn-block">
                            <i class="fas fa-file-pdf"></i> <b>Generar Certificación Técnica</b>
                        </a>

                        @if ($tramite->tramite_tipo_id == 2)
                            {{-- ID 2 = División --}}
                            {{-- <a href="{{ route('admin.tramites.division.execute', $tramite) }}" 
                                class="btn btn-danger btn-block mt-2">
                                <i class="fas fa-project-diagram"></i> <b>Ejecutar División</b>
                            </a> --}}
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PARA SUBIR DOCUMENTOS --}}
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.tramites.addDocumento', $tramite) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="requisito_id" name="requisito_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Adjuntar Documento</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p>Adjuntando para el requisito: <strong id="requisito_nombre"></strong></p>
                        <div class="form-group">
                            <label for="documento">Seleccionar Archivo (PDF, JPG, PNG)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="documento" id="documento"
                                    required>
                                <label class="custom-file-label" for="documento">Elegir archivo...</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Subir Documento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL PARA CAMBIAR ESTADO DEL DOCUMENTO --}}
    <div class="modal fade" id="statusModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="statusForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Revisar Documento</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="documento_estado_id">Nuevo Estado (*)</label>
                            <select id="documento_estado_id" name="estado_id" class="form-control" required></select>
                        </div>
                        <div class="form-group" id="observaciones_wrapper" style="display: none;">
                            <label for="observaciones">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" class="form-control" rows="3"
                                placeholder="Explique el motivo de la observación o rechazo..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        // Pasamos los datos de PHP a JavaScript de forma segura
        const todosLosDocumentoEstados = @json(App\Models\DocumentoEstado::all(['id', 'nombre']));

        $(document).ready(function() {
            // Lógica para el modal de SUBIDA
            $('.btn-upload').on('click', function() {
                var requisitoId = $(this).data('requisito-id');
                var requisitoNombre = $(this).data('requisito-nombre');
                $('#requisito_id').val(requisitoId);
                $('#requisito_nombre').text(requisitoNombre);
                $('#uploadModal').modal('show');
            });

            $('.custom-file-input').on('change', function(event) {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });

            // Lógica para el modal de CAMBIO DE ESTADO
            $('.btn-change-status').on('click', function() {
                var documentoId = $(this).data('documento-id');
                var estadoActualId = $(this).data('estado-actual-id');
                var observacionesActuales = $(this).data('observaciones');

                var url =
                    `{{ url('admin/tramites/' . $tramite->id . '/documentos') }}/${documentoId}/update-status`;
                $('#statusForm').attr('action', url);

                var estadoSelect = $('#documento_estado_id');
                estadoSelect.empty();
                todosLosDocumentoEstados.forEach(function(estado) {
                    estadoSelect.append($('<option>', {
                        value: estado.id,
                        text: estado.nombre
                    }));
                });

                estadoSelect.val(estadoActualId);
                $('#observaciones').val(observacionesActuales);

                estadoSelect.trigger('change'); // Simular cambio para mostrar/ocultar observaciones
                $('#statusModal').modal('show');
            });

            $('#documento_estado_id').on('change', function() {
                var selectedText = $(this).find('option:selected').text().toUpperCase();
                if (selectedText === 'OBSERVADO' || selectedText === 'RECHAZADO') {
                    $('#observaciones_wrapper').slideDown();
                } else {
                    $('#observaciones_wrapper').slideUp();
                }
            });
        });

        // Modal para Observar o Paralizar
        $('#modalExcepcion').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var accion = button.data('accion');
            var modal = $(this);

            modal.find('#inputAccionExcepcion').val(accion);

            if (accion === 'observar') {
                modal.find('#tituloModalExcepcion').text('Observar Trámite');
                modal.find('#btnSubmitExcepcion').removeClass('btn-danger').addClass('btn-warning').text(
                    'Confirmar Observación');
            } else {
                modal.find('#tituloModalExcepcion').text('Paralizar Trámite');
                modal.find('#btnSubmitExcepcion').removeClass('btn-warning').addClass('btn-danger').text(
                    'Confirmar Paralización');
            }
        });
    </script>
@stop
