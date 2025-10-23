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

    {{-- Alerta para mostrar el código de acceso recién generado --}}
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
                                @forelse($tramite->tipo->requisitos as $requisito)
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
                                                <a href="{{ Storage::disk('s3')->url($documento->ruta_archivo) }}"
                                                    target="_blank" class="btn btn-xs btn-info" title="Ver Documento"><i
                                                        class="fas fa-eye"></i> Ver</a>

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
            {{-- Card para actualizar estado general del trámite --}}
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Trámite</h3>
                </div>
                <form action="{{ route('admin.tramites.updateStatus', $tramite) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label>Cambiar Estado del Trámite</label>
                            <select name="estado_id" class="form-control">
                                @foreach ($estados_disponibles as $estado)
                                    <option value="{{ $estado->id }}"
                                        {{ $tramite->estado_id == $estado->id ? 'selected' : '' }}>
                                        {{ $estado->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha de Inspección</label>
                            <input type="date" name="fecha_inspeccion" class="form-control"
                                value="{{ optional($tramite->fecha_inspeccion)->format('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label>Añadir Observación General</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Añada una nueva observación..."></textarea>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="#" class="btn btn-default"><i class="fas fa-dollar-sign"></i> Generar Orden de
                            Pago</a>
                        <button type="submit" class="btn btn-primary">Actualizar Trámite</button>
                    </div>
                </form>
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
    </script>
@stop
