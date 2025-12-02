@extends('adminlte::page')

@section('title', 'Ejecutar División de Predio')

@section('plugins.Select2', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <h1>Ejecutar División de Predio <small>(Trámite #{{ $tramite->id }})</small></h1>
@stop

@section('css')
<style>
    /* Hack CRÍTICO para que funcione la validación HTML5 (required) en selects de Select2 */
    .select2-hidden-accessible {
        border: 0 !important;
        clip: rect(0 0 0 0) !important;
        height: 1px !important;
        margin: -1px !important;
        overflow: hidden !important;
        padding: 0 !important;
        position: absolute !important;
        width: 1px !important;
        display: block !important; /* Importante: sobrescribe el display:none de Select2 */
    }
</style>
@stop

@section('content')
    <form action="{{ route('admin.tramites.division.store', $tramite) }}" method="POST" enctype="multipart/form-data" id="divisionForm">
        @csrf

        {{-- Información del Predio Original --}}
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Predio a Dividir (Se dará de baja)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><strong>Código Catastral:</strong> {{ $predioOriginal->codigo_catastral }}</div>
                    <div class="col-md-3"><strong>Propietario(s):</strong> 
                        @foreach($predioOriginal->propietarios as $prop)
                            {{ $prop->persona->nombre_completo }}<br>
                        @endforeach
                    </div>
                    <div class="col-md-3"><strong>Superficie:</strong> {{ $predioOriginal->sup_levantamiento }} m²</div>
                    <div class="col-md-3"><strong>Ubicación:</strong> {{ $predioOriginal->zona }}, Manzano {{ $predioOriginal->manzano }}, Lote {{ $predioOriginal->lote }}</div>
                </div>
            </div>
        </div>

        {{-- Datos del Testimonio de División --}}
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Datos Legales de la División</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Número de Testimonio</label>
                        <input type="text" name="testimonio_numero" class="form-control" value="{{ old('testimonio_numero') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Fecha de Testimonio</label>
                        <input type="date" name="testimonio_fecha" class="form-control" value="{{ old('testimonio_fecha') }}">
                    </div>
                </div>
            </div>
        </div>
        
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>¡Error!</strong> Revise los campos obligatorios.<br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Contenedor de Predios Resultantes --}}
        <div id="predios-container">
            {{-- Si hay datos previos (old inputs por error de validación), renderizarlos --}}
            @if(old('predios'))
                @foreach(old('predios') as $key => $value)
                     @include('admin.tramites.division.partials._predio_form', ['index' => $key])
                @endforeach
            @else
                {{-- Por defecto mostramos 2 predios --}}
                @include('admin.tramites.division.partials._predio_form', ['index' => 0])
                @include('admin.tramites.division.partials._predio_form', ['index' => 1])
            @endif
        </div>

        <div class="row mt-3 mb-5">
            <div class="col-md-12 text-center">
                <button type="button" class="btn btn-success btn-lg" id="btnAddPredio">
                    <i class="fas fa-plus-circle"></i> Agregar Otro Lote Resultante
                </button>
            </div>
        </div>

        <hr>
        <div class="form-group text-right mb-5">
            <a href="{{ route('admin.tramites.show', $tramite) }}" class="btn btn-secondary btn-lg">Cancelar</a>
            <button type="button" class="btn btn-primary btn-lg" id="btnGuardarDivision">
                <i class="fas fa-save"></i> Ejecutar División y Crear Predios
            </button>
        </div>
    </form>

    {{-- Template oculto para clonar --}}
    <div id="predio-template" style="display: none;">
        @include('admin.tramites.division.partials._predio_form', ['index' => 'TEMPLATE_INDEX', 'is_template' => true])
    </div>

@stop

@section('js')
<script>
    $(document).ready(function() {
        
        // Inicializar Select2 en los elementos existentes
        $('.select2-dynamic').select2({ width: '100%', placeholder: "Seleccione" });

        let predioIndex = {{ old('predios') ? count(old('predios')) : 2 }};

        // Función para recalcular los números visuales
        function updatePredioNumbers() {
            // Solo iterar sobre los cards dentro del contenedor visible, excluyendo el template
            $('#predios-container .predio-card').each(function(index) {
                $(this).find('.predio-number').text(index + 1);
            });
        }

        // Función para agregar nuevo predio
        $('#btnAddPredio').click(function() {
            let template = $('#predio-template').html();
            
            // Reemplazar TEMPLATE_INDEX por el nuevo índice (para IDs y names únicos)
            let newHtml = template.replace(/TEMPLATE_INDEX/g, predioIndex);
            
            // Reemplazar TEMPLATE_LABEL temporalmente
            // Contamos solo los elementos visibles en el contenedor
            let nextVisualNumber = $('#predios-container .predio-card').length + 1;
            newHtml = newHtml.replace(/TEMPLATE_LABEL/g, nextVisualNumber);
            
            // Añadir al contenedor
            $('#predios-container').append(newHtml);

            // Inicializar Select2 en el nuevo elemento
            let newBlock = $('#predio-card-' + predioIndex);
            newBlock.find('.select2-dynamic').select2({ width: '100%', placeholder: "Seleccione" });

            // Scroll suave hacia el nuevo elemento
            $('html, body').animate({
                scrollTop: newBlock.offset().top - 100
            }, 500);

            predioIndex++;
            updatePredioNumbers(); // Asegurar secuencia correcta
        });

        // Evento delegado para eliminar predios
        $(document).on('click', '.btn-remove-predio', function() {
            $(this).closest('.predio-card').remove();
            updatePredioNumbers(); // Actualizar numeración visual tras borrar
        });

        // Confirmación antes de guardar
        $('#btnGuardarDivision').click(function() {
            var form = document.getElementById('divisionForm');
            
            // Validación manual para Select2 (ya que HTML5 ignora elementos ocultos)
            var missingSelects = false;
            $(form).find('select[required]').each(function() {
                if (!$(this).val() || $(this).val().length === 0) {
                    missingSelects = true;
                    // Marcar visualmente el borde del Select2
                    $(this).next('.select2-container').find('.select2-selection').css('border', '1px solid red');
                } else {
                    $(this).next('.select2-container').find('.select2-selection').css('border', '');
                }
            });

            if (missingSelects) {
                Swal.fire({
                    type: 'error',
                    title: 'Campos incompletos',
                    text: 'Por favor seleccione una opción en los campos marcados en rojo.'
                });
                return;
            }
            
            // 1. Validar el resto formulario usando la validación nativa del navegador
            if (!form.checkValidity()) {
                form.reportValidity(); // Muestra los globos de error nativos
                return; // Detiene la ejecución
            }

            // 2. Si es válido, mostrar SweetAlert
            Swal.fire({
                title: '¿Está seguro de ejecutar la división?',
                text: "Esta acción dará de BAJA el predio original y creará los nuevos predios definidos. Esta acción es irreversible.",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, ejecutar división'
            }).then((result) => {
                if (result.value || result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Actualizar nombre de archivos seleccionados
        $(document).on('change', '.custom-file-input', function(event) {
             var files = event.target.files;
             var names = [];
             for (var i = 0; i < files.length; i++) {
                 names.push(files[i].name);
             }
             $(this).next('.custom-file-label').html(names.join(', ') || 'Elegir archivos...');
        });

        // Lógica de autocompletado de Lote (replicada y adaptada)
        $(document).on('blur', '.input-manzano', function() {
             let manzanoInput = $(this);
             let manzano = manzanoInput.val();
             let parentCard = manzanoInput.closest('.card-body');
             let loteInput = parentCard.find('.input-lote');
             
             if (manzano && !loteInput.val()) { // Solo si lote está vacío para no sobrescribir manuales
                $.ajax({
                    url: "{{ route('admin.predios.nextLote') }}",
                    data: { manzano: manzano },
                    success: function(response) {
                        if (response.next_lote) {
                            loteInput.val(response.next_lote);
                            // Disparar evento blur para actualizar código si implementamos esa lógica
                        }
                    }
                });
             }
        });
    });
</script>
@stop
