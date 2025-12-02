@extends('adminlte::page')

@section('title', 'Generar Resolución de División')
@section('plugins.Select2', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <h1>Generar Resolución de División (Trámite #{{ $tramite->id }})</h1>
@stop

@section('css')
<style>
    /* Hack para que funcione la validación HTML5 (required) en selects de Select2 */
    .select2-hidden-accessible {
        border: 0 !important;
        clip: rect(0 0 0 0) !important;
        height: 1px !important;
        margin: -1px !important;
        overflow: hidden !important;
        padding: 0 !important;
        position: absolute !important;
        width: 1px !important;
        display: block !important; 
    }
</style>
@stop

@section('content')
    <form action="{{ route('admin.tramites.generateDivision', $tramite) }}" method="POST" id="divisionForm">
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

        {{-- Datos del Predio Original (Referencia) --}}
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">Predio Original (Se dará de baja al generar)</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><strong>Código:</strong> {{ $tramite->predio->codigo_catastral }}</div>
                    <div class="col-md-3"><strong>Superficie:</strong> {{ $tramite->predio->sup_levantamiento }} m²</div>
                    <div class="col-md-6"><strong>Propietarios:</strong>
                         @foreach($tramite->predio->propietarios as $prop)
                            {{ $prop->persona->nombre_completo }}@if(!$loop->last), @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">1. Datos del Documento Legal</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="testimonio_numero">N° de Testimonio (*)</label>
                        <input type="text" class="form-control" id="testimonio_numero" name="testimonio_numero" value="{{ old('testimonio_numero', '1459/2024') }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="testimonio_fecha">Fecha de Testimonio (*)</label>
                        <input type="date" class="form-control" id="testimonio_fecha" name="testimonio_fecha" value="{{ old('testimonio_fecha', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-12 form-group">
                        <label for="superficie_total">Superficie Total Original (m²) (*)</label>
                        <input type="number" step="0.01" class="form-control" id="superficie_total" name="superficie_total" value="{{ old('superficie_total', $tramite->predio->sup_levantamiento ?? '') }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">2. Lotes Resultantes (Seleccionar Predios Creados)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool btn-success" id="add-inciso">
                        <i class="fas fa-plus"></i> Añadir Predio Resultante
                    </button>
                </div>
            </div>
            <div class="card-body" id="incisos-container">
                <p class="text-muted mb-3"><i class="fas fa-info-circle"></i> Seleccione los predios nuevos que forman parte de esta división. Los datos técnicos (superficie, colindantes) se tomarán automáticamente del predio seleccionado.</p>
                
                {{-- Primer inciso --}}
                <div class="inciso-item card card-outline card-secondary p-3" data-index="0">
                    <div class="d-flex justify-content-between">
                        <h5 class="text-primary">Predio Resultante #1</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Seleccionar Predio Nuevo (*)</label>
                            <select name="incisos[0][predio_id]" class="form-control select2-predios" required>
                                <option value="">-- Buscar por Código o Propietario --</option>
                                @foreach($prediosDisponibles as $predio)
                                    <option value="{{ $predio->id }}" data-superficie="{{ $predio->sup_levantamiento }}" data-lote="{{ $predio->lote }}">
                                        {{ $predio->codigo_catastral }} - {{ $predio->propietarios->first()->persona->nombre_completo ?? 'S/P' }} ({{ $predio->sup_levantamiento }} m²)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Denominativo (PDF) (*)</label>
                            <input type="text" name="incisos[0][denominativo]" class="form-control" placeholder="Ej: LOTE 4-A" value="{{ old('incisos.0.denominativo') }}" required>
                            <small class="text-muted">Este nombre aparecerá en el PDF como el identificador del lote.</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Nuevo Número de Lote (*)</label>
                            <input type="text" name="incisos[0][lote_nuevo]" class="form-control" placeholder="Ej: 24" value="{{ old('incisos.0.lote_nuevo') }}" required readonly>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Sup. Legal % (Auto)</label>
                            <input type="number" step="0.01" name="incisos[0][superficie_legal_porcentaje]" class="form-control input-porcentaje" value="0" readonly>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Sup. Util % (Auto)</label>
                            <input type="number" step="0.01" name="incisos[0][superficie_util_porcentaje]" class="form-control input-porcentaje" value="0" readonly>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger btn-remove-inciso" style="display: none;">Quitar este lote</button>
                </div>
                
                {{-- Segundo inciso --}}
                 <div class="inciso-item card card-outline card-secondary p-3" data-index="1">
                    <div class="d-flex justify-content-between">
                        <h5 class="text-primary">Predio Resultante #2</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Seleccionar Predio Nuevo (*)</label>
                            <select name="incisos[1][predio_id]" class="form-control select2-predios" required>
                                <option value="">-- Buscar por Código o Propietario --</option>
                                @foreach($prediosDisponibles as $predio)
                                    <option value="{{ $predio->id }}" data-superficie="{{ $predio->sup_levantamiento }}" data-lote="{{ $predio->lote }}">
                                        {{ $predio->codigo_catastral }} - {{ $predio->propietarios->first()->persona->nombre_completo ?? 'S/P' }} ({{ $predio->sup_levantamiento }} m²)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Denominativo (PDF) (*)</label>
                            <input type="text" name="incisos[1][denominativo]" class="form-control" placeholder="Ej: LOTE 4-B" value="{{ old('incisos.1.denominativo') }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Nuevo Número de Lote (*)</label>
                            <input type="text" name="incisos[1][lote_nuevo]" class="form-control" placeholder="Ej: 25" value="{{ old('incisos.1.lote_nuevo') }}" required readonly>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Sup. Legal % (Auto)</label>
                            <input type="number" step="0.01" name="incisos[1][superficie_legal_porcentaje]" class="form-control input-porcentaje" value="0" readonly>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Sup. Util % (Auto)</label>
                            <input type="number" step="0.01" name="incisos[1][superficie_util_porcentaje]" class="form-control input-porcentaje" value="0" readonly>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger btn-remove-inciso" style="display: none;">Quitar este lote</button>
                </div>
            </div>
        </div>

        <div class="mt-3 mb-5 text-right">
            <a href="{{ route('admin.tramites.show', $tramite) }}" class="btn btn-secondary btn-lg">Cancelar</a>
            <button type="button" class="btn btn-primary btn-lg" id="btnGenerarPdf">
                <i class="fas fa-file-pdf"></i> Generar Resolución PDF
            </button>
        </div>
    </form>
@stop

@section('js')
<script>
    $(document).ready(function() {
        function initSelect2() {
            $('.select2-predios').select2({
                width: '100%',
                placeholder: "-- Seleccione Predio --",
                allowClear: true
            });
        }
        initSelect2();

        var incisoIndex = 2;

        var incisoTemplate = `
            <div class="inciso-item card card-outline card-secondary p-3" data-index="__INDEX__">
                 <div class="d-flex justify-content-between">
                    <h5 class="text-primary">Predio Resultante #__COUNT__</h5>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Seleccionar Predio Nuevo (*)</label>
                        <select name="incisos[__INDEX__][predio_id]" class="form-control select2-predios" required>
                            <option value="">-- Buscar por Código o Propietario --</option>
                            @foreach($prediosDisponibles as $predio)
                                <option value="{{ $predio->id }}" data-superficie="{{ $predio->sup_levantamiento }}" data-lote="{{ $predio->lote }}">
                                    {{ $predio->codigo_catastral }} - {{ $predio->propietarios->first()->persona->nombre_completo ?? 'S/P' }} ({{ $predio->sup_levantamiento }} m²)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Denominativo (PDF) (*)</label>
                        <input type="text" name="incisos[__INDEX__][denominativo]" class="form-control" placeholder="Ej: LOTE 4-C" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Nuevo Número de Lote (*)</label>
                        <input type="text" name="incisos[__INDEX__][lote_nuevo]" class="form-control" placeholder="Ej: 26" required readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Sup. Legal % (Auto)</label>
                        <input type="number" step="0.01" name="incisos[__INDEX__][superficie_legal_porcentaje]" class="form-control input-porcentaje" value="0" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Sup. Util % (Auto)</label>
                        <input type="number" step="0.01" name="incisos[__INDEX__][superficie_util_porcentaje]" class="form-control input-porcentaje" value="0" readonly>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger btn-remove-inciso">Quitar este lote</button>
            </div>`;

        $('#add-inciso').on('click', function() {
            var newInciso = incisoTemplate.replace(/__INDEX__/g, incisoIndex).replace(/__COUNT__/g, incisoIndex + 1);
            var $newElement = $(newInciso);
            $('#incisos-container').append($newElement);
            $newElement.find('.select2-predios').select2({
                width: '100%',
                placeholder: "-- Seleccione Predio --",
                allowClear: true
            });
            incisoIndex++;
            updateRemoveButtons();
        });

        $('#incisos-container').on('click', '.btn-remove-inciso', function() {
            $(this).closest('.inciso-item').remove();
            updateRemoveButtons();
            recalculateAllPercentages(); // Recalcular tras borrar
        });

        function updateRemoveButtons() {
            var items = $('.inciso-item');
            if (items.length <= 2) {
                items.find('.btn-remove-inciso').hide();
            } else {
                items.find('.btn-remove-inciso').show();
            }
            
            items.each(function(index) {
                $(this).find('h5.text-primary').text('Predio Resultante #' + (index + 1));
            });
        }

        // --- CÁLCULO AUTOMÁTICO DE PORCENTAJES Y DATOS ---

        function calculatePercentageAndFillData(rowContext) {
            var select = rowContext.find('.select2-predios');
            var option = select.find('option:selected');
            
            var superficiePredio = parseFloat(option.data('superficie')) || 0;
            var lotePredio = option.data('lote');
            
            if (lotePredio !== undefined && lotePredio !== null) {
                rowContext.find('input[name$="[lote_nuevo]"]').val(lotePredio);
            }

            var superficieTotal = parseFloat($('#superficie_total').val()) || 0;

            if (superficieTotal > 0 && superficiePredio > 0) {
                var porcentaje = (superficiePredio / superficieTotal) * 100;
                var porcentajeRedondeado = porcentaje.toFixed(2);
                
                rowContext.find('input[name$="[superficie_legal_porcentaje]"]').val(porcentajeRedondeado);
                rowContext.find('input[name$="[superficie_util_porcentaje]"]').val(porcentajeRedondeado);
            } else {
                rowContext.find('input[name$="[superficie_legal_porcentaje]"]').val(0);
                rowContext.find('input[name$="[superficie_util_porcentaje]"]').val(0);
            }
        }

        $(document).on('change', '.select2-predios', function() {
            var row = $(this).closest('.inciso-item');
            calculatePercentageAndFillData(row);
        });

        $('#superficie_total').on('input change', function() {
            recalculateAllPercentages();
        });

        function recalculateAllPercentages() {
             $('.inciso-item').each(function() {
                 calculatePercentageAndFillData($(this));
             });
        }

        // VALIDACIÓN Y SWEETALERT
        $('#btnGenerarPdf').click(function() {
            var form = document.getElementById('divisionForm');

            // 1. Validación Nativa (con el hack CSS debería funcionar bien con select2)
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // 2. Validación de Porcentajes
            var totalPorcentaje = 0;
            $('.input-porcentaje[name$="[superficie_legal_porcentaje]"]').each(function() {
                totalPorcentaje += parseFloat($(this).val()) || 0;
            });

            if (totalPorcentaje < 99.9 || totalPorcentaje > 100.1) {
                Swal.fire({
                    type: 'error', // Usamos 'type' para compatibilidad antigua, aunque 'icon' es lo moderno
                    title: 'Error de Superficies',
                    text: 'La suma de los porcentajes es ' + totalPorcentaje.toFixed(2) + '%. Debe ser 100%. Revise las superficies.'
                });
                return;
            }

            // 3. Confirmación
            Swal.fire({
                title: '¿Generar Resolución?',
                text: "Se desactivará el predio original y se generará el documento PDF. Esta acción es irreversible.",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, generar'
            }).then((result) => {
                if (result.value || result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Calcular inicialmente
        recalculateAllPercentages();
    });
</script>
@stop
