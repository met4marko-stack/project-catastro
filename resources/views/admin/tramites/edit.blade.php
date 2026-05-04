@extends('adminlte::page')

@section('title', 'Editar Trámite')

@section('plugins.Select2', true)

@section('css')
<style>
    /* Corrige el problema de alineación de Select2 dentro de un input-group */
    .input-group .select2-container {
        flex: 1 1 auto;
    }
</style>
@stop

@section('content_header')
    <h1><b>Editar Trámite: {{ $tramite->hoja_ruta ?? $tramite->id }}</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifique los datos del trámite</h3>
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

            <form action="{{ route('admin.tramites.update', $tramite) }}" method="POST">
                @csrf
                @method('PUT')
                
                {{-- 
                    Pasamos la variable $tramite al formulario parcial.
                    El formulario ahora se llenará con los datos de $tramite.
                --}}
                @include('admin.tramites.partials._form-fields', ['tramite' => $tramite])
                
                <hr>
                <div class="form-group text-right">
                    <a href="{{ route('admin.tramites.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Trámite</button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Inicializar Select2
        // Los valores ya se establecen desde el HTML gracias a la sintaxis 'old(...)'
        $('.select2').select2({
            placeholder: "Seleccione una opción",
            allowClear: true
        });

        // --- (Aquí va todo el código JS del modal "Nuevo Apoderado") ---
        $('#btn-guardar-apoderado').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
            $('#apoderado-errors').hide().empty();

            $.ajax({
                url: "{{ route('admin.personas.storeAjax') }}",
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
                        $('#solicitante_id').append(newOption).trigger('change');
                        $('#form-nuevo-apoderado input').val('');
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