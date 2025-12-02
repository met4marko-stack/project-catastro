@extends('adminlte::page')

@section('title', 'Registrar Predio')

{{-- Activa el plugin de Select2 --}}
@section('plugins.Select2', true)

@section('content_header')
    <h1>
        <b>Registrar Nuevo Predio</b>
    </h1>
@stop

@section('content')
    {{-- Sección para la IA --}}
    <!--
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">Opcional: Rellenar datos desde Plano Catastral</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="documento_plano">Subir Plano (PDF o Imagen)</label>
                <div class="input-group">
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" id="documento_plano" accept=".pdf,.jpg,.jpeg,.png">
                        <label class="custom-file-label" for="documento_plano">Seleccionar archivo</label>
                    </div>
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" id="btnProcesarPlano"><i class="fas fa-cogs"></i>
                            Procesar con IA</button>
                    </div>
                </div>
                <div id="ocr-spinner" class="mt-2" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> Procesando documento, esto puede tardar un momento...
                </div>
            </div>
        </div>
    </div>-->

    <form action="{{ route('admin.predios.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
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

        @include('admin.predios.partials._form-fields')

        <hr>
        <div class="form-group text-right">
            <a href="{{ route('admin.predios.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Registrar Predio</button>
        </div>
    </form>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#documento_plano').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName || 'Seleccionar archivo');
            });

            // 1. Inicializar Select2
            $('.select2').select2({
                placeholder: "Seleccione una opción",
                allowClear: true
            });

            // 2. Lógica para Propiedad Horizontal
            function toggleUnidadFields() {
                if ($('#propiedad_horizontal').is(':checked')) {
                    $('#campos_unidad').slideUp();
                    // Limpiar campos de unidad cuando se marca como padre
                    $('#campos_unidad').find('select, input').val(null).trigger('change');
                } else {
                    $('#campos_unidad').slideDown();
                }
            }
            $('#propiedad_horizontal').on('change', toggleUnidadFields);
            toggleUnidadFields(); // Llamada inicial para establecer el estado correcto

            // 3. Script para mostrar el nombre de los archivos múltiples
            $('#fotografias').on('change', function(event) {
                var files = event.target.files;
                if (files.length > 5) {
                    //alert("¡Solo puedes subir un máximo de 5 imágenes!");
                    Swal.fire('¡Solo puedes subir un máximo de 5 imágenes!');
                    $(this).val(''); // Limpiar la selección
                    $(this).next('.custom-file-label').html('Elegir archivos...');
                    return;
                }
                var fileNames = [];
                for (var i = 0; i < files.length; i++) {
                    fileNames.push(files[i].name);
                }
                $(this).next('.custom-file-label').html(fileNames.join(', '));
            });

            // 4. Lógica AJAX para procesar plano con IA
            $('#btnProcesarPlano').on('click', function() {
                var fileInput = $('#documento_plano')[0];
                if (fileInput.files.length === 0) {
                    Swal.fire('Atención', 'Por favor, seleccione un archivo de plano.', 'warning');
                    return;
                }
                var formData = new FormData();
                formData.append('documento', fileInput.files[0]);
                formData.append('_token', '{{ csrf_token() }}');

                $('#ocr-spinner').show();
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

                $.ajax({
                    url: "{{ route('admin.predios.procesarPlano') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Rellenar campos del formulario con la respuesta de la IA
                        $('input[name="numero_plano"]').val(response.numero_plano || '');

                        let iden = response.identificacion || {};
                        let distrito = iden.distrito || '01';
                        let codigo = distrito + '-' + (iden.manzano || '') + '-' + (iden.lote ||
                            '');
                        $('input[name="codigo_catastral"]').val(codigo);
                        $('input[name="manzano"]').val(iden.manzano || '');
                        $('input[name="lote"]').val(iden.lote || '');

                        let ubi = response.ubicacion || {};
                        $('select[name="provincia_id"]').val(response.provincia_id || '').trigger('change'); 
                        $('select[name="centro_poblado_id"]').val(response.centro_poblado_id || '').trigger('change');
                        $('input[name="zona"]').val(ubi.zona || '');

                        let sup = response.superficies || {};
                        $('input[name="sup_levantamiento"]').val(sup.segun_levantamiento || '');
                        $('input[name="sup_testimonio"]').val(sup.segun_testimonio || '');
                        $('input[name="sup_construida"]').val(sup.construida || '');
                        $('input[name="sup_afectada"]').val(sup.afectada || '');
                        $('input[name="sup_util"]').val(sup.util || '');

                        let col = response.colindantes || {};
                        $('input[name="colindante_norte"]').val(col.norte || '');
                        $('input[name="colindante_sur"]').val(col.sur || '');
                        $('input[name="colindante_este"]').val(col.este || '');
                        $('input[name="colindante_oeste"]').val(col.oeste || '');

                        $('input[name="frente_principal"]').val(response.frente_principal ||
                            '');
                        $('input[name="material_via"]').val(response.material_via || '');
                        $('input[name="forma_lote"]').val(response.forma_lote || '');

                        let serv = response.servicios_basicos || {};
                        $('#agua_potable').prop('checked', serv.agua_potable || false);
                        $('#energia_electrica').prop('checked', serv.energia_electrica ||
                            false);
                        $('#alcantarillado').prop('checked', serv.alcantarillado || false);
                        $('#alumbrado_publico').prop('checked', serv.alumbrado_publico ||
                            false);
                        $('#gas_domiciliario').prop('checked', serv.gas_domiciliario || false);

                        if (response.coordenadas_utm) {
                            $('#coordenadas_text').val(JSON.stringify(response
                                .coordenadas_utm));
                        }

                        if (response.propietario_id_encontrado) {
                            // Selecciona el propietario en el Select2 y dispara el evento 'change'
                            $('#propietarios').val(response.propietario_id_encontrado).trigger(
                                'change');

                            // Muestra una notificación más específica
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: 'Datos extraídos y propietario encontrado. Por favor, verifique la información.',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Datos Extraídos',
                                'No se encontró un propietario con la C.I. del plano. Por favor, selecciónelo manualmente.',
                                'info');
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON ? xhr.responseJSON.error :
                            'Ocurrió un error desconocido.';
                        Swal.fire('Error', errorMsg, 'error');
                    },
                    complete: function() {
                        $('#ocr-spinner').hide();
                        $('#btnProcesarPlano').prop('disabled', false).html(
                            '<i class="fas fa-cogs"></i> Procesar con IA');
                    }
                });
            });

            // 5. Autocompletado de Lote al cambiar el Manzano
            $('input[name="manzano"]').on('blur', function() {
                var manzano = $(this).val();
                if (manzano) {
                    $.ajax({
                        url: "{{ route('admin.predios.nextLote') }}",
                        data: { manzano: manzano },
                        success: function(response) {
                            if (response.next_lote) {
                                // Ponemos el número en el input de lote
                                $('input[name="lote"]').val(response.next_lote);
                                updateCodigoCatastral(); // Llama a la función para actualizar el código catastral
                            }
                        }
                    });
                }
            });

            // Función para actualizar el código catastral
            function updateCodigoCatastral() {
                let manzano = $('input[name="manzano"]').val();
                let lote = $('input[name="lote"]').val();
                const distrito = '01'; // Valor fijo

                let formattedManzano = '';
                // Si es numérico y entre 1-9, poner prefijo 0. Si es >=10 o alfanumérico, dejar tal cual.
                // Nota: parseInt("05") es 5.
                if (manzano && !isNaN(parseInt(manzano))) {
                    let mInt = parseInt(manzano);
                    if (mInt >= 1 && mInt <= 9) {
                        formattedManzano = '0' + mInt;
                    } else {
                        formattedManzano = String(mInt);
                    }
                } else if (manzano) {
                    formattedManzano = manzano;
                }

                let formattedLote = '';
                if (lote && !isNaN(parseInt(lote))) {
                    let lInt = parseInt(lote);
                    if (lInt >= 1 && lInt <= 9) {
                        formattedLote = '0' + lInt;
                    } else {
                        formattedLote = String(lInt);
                    }
                } else if (lote) {
                    formattedLote = lote;
                }
                
                if (formattedManzano && formattedLote) {
                    let codigoCatastral = distrito + formattedManzano + formattedLote;
                    $('input[name="codigo_catastral"]').val(codigoCatastral);
                } else {
                    $('input[name="codigo_catastral"]').val('');
                }
            }

            // Escuchar cambios en manzano y lote para actualizar el código catastral
            $('input[name="manzano"]').on('blur', updateCodigoCatastral);
            $('input[name="lote"]').on('blur', updateCodigoCatastral);

        });
    </script>
@stop
