@extends('adminlte::page')

@section('title', 'Iniciar Nuevo Trámite')

@section('plugins.Select2', true)

@section('css')
    <style>
        .input-group .select2-container {
            flex: 1 1 auto;
        }
    </style>
@stop

@section('content_header')
    <h1><b>Iniciar Nuevo Trámite</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Complete los datos del nuevo trámite</h3>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>¡Error!</strong> Por favor, revise los siguientes campos:<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.tramites.store') }}" method="POST">
                @csrf
                @include('admin.tramites.partials._form-fields', ['tramite' => $tramite])

                <hr>
                <div class="form-group text-right">
                    <a href="{{ route('admin.tramites.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Registrar Trámite</button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    
    // --- INICIALIZACIÓN DE SELECTS ---
    
    // --- INICIO DE LA CORRECCIÓN ---
    // 1. Crear una plantilla de URL que Laravel genere correctamente
    const propietariosUrlTemplate = "{{ route('admin.predios.getPropietariosAjax', ['predio' => 'ID_PLACEHOLDER']) }}";
    // --- FIN DE LA CORRECCIÓN ---

    var selectPropietarios = $('#solicitante_id_propietario');
    var selectApoderados = $('#solicitante_id_apoderado');

    // Selects estáticos (Tipos de Trámite y Predio)
    $('#tramite_tipo_id, #predio_id').select2({
        placeholder: "Seleccione una opción",
        allowClear: true
    });

    // Select para PROPIETARIOS (se inicializa vacío)
    selectPropietarios.select2({
        placeholder: "Seleccione un propietario",
        allowClear: true
    });

    // Select para APODERADOS (con búsqueda AJAX)
    selectApoderados.select2({
        placeholder: "Buscar por nombre o C.I.",
        allowClear: true,
        ajax: {
            url: "{{ route('admin.personas.searchAjax') }}", // Esta ruta ya era correcta
            dataType: 'json',
            delay: 250, 
            processResults: function (data) {
                return {
                    results: data.results
                };
            },
            cache: true
        }
    });

    // --- MANEJO DE LA LÓGICA DEL FORMULARIO ---

    // 1. Cuando se selecciona un PREDIO
    $('#predio_id').on('change', function() {
        var predioId = $(this).val();
        
        $('input[name="solicitante_tipo"]').prop('checked', false);
        $('#propietario_select_wrapper').hide();
        $('#apoderado_select_wrapper').hide();
        $('#solicitante_id_hidden').val(''); 

        if (predioId) {
            $('input[name="solicitante_tipo"]').prop('disabled', false);
        } else {
            $('input[name="solicitante_tipo"]').prop('disabled', true);
        }
    });

    // 2. Cuando se selecciona "Propietario" o "Apoderado" (RADIO BUTTONS)
    $('input[name="solicitante_tipo"]').on('change', function() {
        var tipo = $(this).val();
        var predioId = $('#predio_id').val();
        
        $('#solicitante_id_hidden').val('');
        selectPropietarios.val(null).trigger('change');
        selectApoderados.val(null).trigger('change');

        if (tipo === 'propietario') {
            $('#apoderado_select_wrapper').hide();
            $('#propietario_select_wrapper').show();
            
            // --- INICIO DE LA CORRECCIÓN ---
            // 2. Usar la plantilla de URL y reemplazar el placeholder con el ID real
            var url = propietariosUrlTemplate.replace('ID_PLACEHOLDER', predioId);
            // --- FIN DE LA CORRECCIÓN ---

            $.ajax({
                url: url, // <-- Se usa la URL corregida
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    selectPropietarios.empty(); 
                    if (data.length > 0) {
                        var placeholder = new Option('Seleccione un propietario', '', true, true);
                        selectPropietarios.append(placeholder).trigger('change');
                        
                        data.forEach(function(propietario) {
                            var option = new Option(propietario.text, propietario.id, false, false);
                            selectPropietarios.append(option);
                        });
                    } else {
                        var noOption = new Option('Este predio no tiene propietarios asociados', '', true, true);
                        selectPropietarios.append(noOption).trigger('change');
                    }
                }
            });

        } else if (tipo === 'apoderado') {
            $('#propietario_select_wrapper').hide();
            $('#apoderado_select_wrapper').show();
        }
    });

    // 3. Guardar el ID del solicitante en el campo oculto
    selectPropietarios.on('change', function() {
        $('#solicitante_id_hidden').val($(this).val());
    });
    selectApoderados.on('change', function() {
        $('#solicitante_id_hidden').val($(this).val());
    });

    // 4. Lógica para guardar el nuevo apoderado (esta ruta ya usaba route())
    $('#btn-guardar-apoderado').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        $('#apoderado-errors').hide().empty();

        $.ajax({
            url: "{{ route('admin.personas.storeAjax') }}", // <-- Esta estaba bien
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                nombre: $('#apoderado_nombre').val(),
                primer_apellido: $('#apoderado_primer_apellido').val(),
                segundo_apellido: $('#apoderado_segundo_apellido').val(),
                carnet: $('#apoderado_carnet').val(),
                expedido: $('#apoderado_expedido').val()
            },
            success: function(response) {
                if (response.success) {
                    var newOption = new Option(response.persona.nombre_completo, response.persona.id, true, true);
                    selectApoderados.append(newOption).trigger('change');
                    $('#solicitante_id_hidden').val(response.persona.id);
                    $('#form-nuevo-apoderado input, #form-nuevo-apoderado select').val('');
                    $('#collapseNuevoApoderado').collapse('hide');
                    Swal.fire('¡Éxito!', response.message, 'success');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    var errorHtml = '<ul>';
                    $.each(errors, function(key, value) {
                        errorHtml += '<li>' + value[0] + '</li>';
                    });
                    errorHtml += '</ul>';
                    $('#apoderado-errors').html(errorHtml).show();
                } else {
                    Swal.fire('Error', 'Ocurrió un error inesperado.', 'error');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('Guardar y Seleccionar');
            }
        });
    });
});
</script>
@stop