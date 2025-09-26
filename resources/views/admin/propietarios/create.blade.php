@extends('adminlte::page')

@section('title', 'Registrar Propietario')

@section('content_header')
    <h1><b>Registrar Nuevo Propietario</b></h1>
@stop

@section('content')
    {{-- Sección para el OCR --}}
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">Opcional: Rellenar datos desde C.I.</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="documento_ci">Subir Carnet de Identidad (PDF o Imagen)</label>
                <div class="input-group">
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="documento_ci" accept=".pdf,.jpg,.jpeg,.png">
                        <label class="custom-file-label" for="documento_ci">Seleccionar archivo</label>
                    </div>
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" id="btnProcesarOcr">
                            <i class="fas fa-cogs"></i> Procesar
                        </button>
                    </div>
                </div>
                <div id="ocr-spinner" class="mt-2" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> Procesando documento, por favor espere...
                </div>
            </div>
        </div>
    </div>


    <div class="card">
        <div class="card-header"><h3 class="card-title">Llene los datos del formulario</h3></div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form action="{{ route('admin.propietarios.store') }}" method="POST">
                @csrf
                @include('admin.propietarios.partials.form-fields')
                <hr>
                <div class="form-group">
                    <a href="{{ route('admin.propietarios.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Registrar Propietario</button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
    // Script para deshabilitar la fecha de caducidad si el carnet es indefinido
    /*document.getElementById('ci_es_indefinido').addEventListener('change', function() {
        var fechaCaducidadInput = document.getElementById('ci_fecha_caducidad');
        if (this.checked) {
            fechaCaducidadInput.disabled = true;
            fechaCaducidadInput.value = ''; // Limpiar el valor
        } else {
            fechaCaducidadInput.disabled = false;
        }
    });*/
    $(document).ready(function() {
        function handleCiExpiration() {
            var isChecked = $('#ci_es_indefinido').is(':checked');
            $('#ci_fecha_caducidad').prop('disabled', isChecked).val(isChecked ? '' : $('#ci_fecha_caducidad').val());
        }
        $('#ci_es_indefinido').on('change', handleCiExpiration);
        handleCiExpiration();

        $('#documento_ci').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName || 'Seleccionar archivo');
        });

        $('#btnProcesarOcr').on('click', function() {
            var fileInput = $('#documento_ci')[0];
            if (fileInput.files.length === 0) {
                Swal.fire('Error', 'Por favor, seleccione un archivo.', 'error');
                return;
            }
            var formData = new FormData();
            formData.append('documento_ci', fileInput.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            $('#ocr-spinner').show();
            $(this).prop('disabled', true);

            $.ajax({
                url: '{{ route("admin.propietarios.procesarOcr") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#ocr-spinner').hide();
                    $('#btnProcesarOcr').prop('disabled', false);

                    $('input[name="nombre"]').val(response.nombre || '');
                    $('input[name="primer_apellido"]').val(response.primer_apellido || '');
                    $('input[name="segundo_apellido"]').val(response.segundo_apellido || '');
                    $('input[name="carnet"]').val(response.carnet || '');
                    $('select[name="expedido"]').val(response.expedido || '');
                    $('input[name="fecha_nacimiento"]').val(response.fecha_nacimiento || '');
                    
                    // --- LÓGICA MEJORADA PARA FECHA DE CADUCIDAD ---
                    $('input[name="ci_fecha_caducidad"]').val(response.ci_fecha_caducidad || '');
                    $('#ci_es_indefinido').prop('checked', response.ci_es_indefinido || false);
                    // Disparamos el evento 'change' para que el script actualice el estado del input de fecha
                    $('#ci_es_indefinido').trigger('change');

                    Swal.fire('¡Éxito!', 'Datos extraídos correctamente. Por favor, verifique la información.', 'success');
                },
                error: function(xhr) {
                    $('#ocr-spinner').hide();
                    $('#btnProcesarOcr').prop('disabled', false);
                    var errorMsg = xhr.responseJSON ? xhr.responseJSON.error : 'Ocurrió un error desconocido.';
                    Swal.fire('Error', errorMsg, 'error');
                }
            });
        });
    });
</script>
@stop

