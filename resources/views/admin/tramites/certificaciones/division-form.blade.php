@extends('adminlte::page')

@section('title', 'Generar Resolución de División')
@section('plugins.Select2', true) {{-- Activar Select2 --}}

@section('content_header')
    <h1>Generar Resolución de División (Trámite #{{ $tramite->id }})</h1>
@stop

@section('content')
    <form action="{{ route('admin.tramites.generateDivision', $tramite) }}" method="POST">
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

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">1. Datos del Documento Legal</h3>
            </div>
            <div class="card-body">
                <p>Propietarios originales del predio:
                    @foreach($tramite->predio->propietarios as $prop)
                        <strong>{{ $prop->persona->nombre_completo }}</strong>@if(!$loop->last), @endif
                    @endforeach
                </p>
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
                        <input type="number" step="0.01" class="form-control" id="superficie_total" name="superficie_total" value="{{ old('superficie_total', $tramite->predio->sup_levantamiento ?? '581.26') }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">2. Lotes Resultantes de la División</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool btn-success" id="add-inciso">
                        <i class="fas fa-plus"></i> Añadir Lote Resultante
                    </button>
                </div>
            </div>
            <div class="card-body" id="incisos-container">
                {{-- El primer inciso (mínimo 1) --}}
                <div class="inciso-item" data-index="0">
                    <h5 class="text-primary">Lote Resultante #1</h5>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Propietario Asignado (*)</label>
                            <select name="incisos[0][propietario_id]" class="form-control select2-propietarios" required>
                                <option value="">-- Seleccione --</option>
                                @foreach($propietarios_lista as $prop)
                                    <option value="{{ $prop->id }}">{{ $prop->persona->nombre_completo }} ({{ $prop->persona->carnet }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Manzano (Ej: 23) (*)</label>
                            <input type="text" name="incisos[0][manzano]" class="form-control" value="{{ old('incisos.0.manzano', $tramite->predio->manzano ?? '23') }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Lote (Ej: 4-A) (*)</label>
                            <input type="text" name="incisos[0][lote]" class="form-control" value="{{ old('incisos.0.lote', '4-A') }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Superficie (Ej: 292.99) (*)</label>
                            <input type="number" step="0.01" name="incisos[0][superficie]" class="form-control" value="{{ old('incisos.0.superficie', '292.99') }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Nuevo Lote (Ej: 24) (*)</label>
                            <input type="text" name="incisos[0][lote_nuevo]" class="form-control" value="{{ old('incisos.0.lote_nuevo', '24') }}" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Sup. Legal % (*)</label>
                            <input type="number" step="0.01" name="incisos[0][superficie_legal_porcentaje]" class="form-control" value="{{ old('incisos.0.superficie_legal_porcentaje', '50.41') }}" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Sup. Util % (*)</label>
                            <input type="number" step="0.01" name="incisos[0][superficie_util_porcentaje]" class="form-control" value="{{ old('incisos.0.superficie_util_porcentaje', '50.41') }}" required>
                        </div>
                    </div>
                    <h6>Colindantes del Nuevo Lote</h6>
                    <div class="row">
                        <div class="col-md-3 form-group"><input type="text" name="incisos[0][col_norte]" class="form-control" placeholder="Colindante Norte (*)" required></div>
                        <div class="col-md-3 form-group"><input type="text" name="incisos[0][col_sur]" class="form-control" placeholder="Colindante Sur (*)" required></div>
                        <div class="col-md-3 form-group"><input type="text" name="incisos[0][col_este]" class="form-control" placeholder="Colindante Este (*)" required></div>
                        <div class="col-md-3 form-group"><input type="text" name="incisos[0][col_oeste]" class="form-control" placeholder="Colindante Oeste (*)" required></div>
                    </div>
                    <button type="button" class="btn btn-xs btn-danger btn-remove-inciso" style="display: none;">Quitar</button>
                    <hr>
                </div>
                
                {{-- Plantilla para el segundo inciso (mínimo 2) --}}
                <div class="inciso-item" data-index="1">
                    <h5 class="text-primary">Lote Resultante #2</h5>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Propietario Asignado (*)</label>
                            <select name="incisos[1][propietario_id]" class="form-control select2-propietarios" required>
                                <option value="">-- Seleccione --</option>
                                @foreach($propietarios_lista as $prop)
                                    <option value="{{ $prop->id }}">{{ $prop->persona->nombre_completo }} ({{ $prop->persona->carnet }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Manzano (Ej: 23) (*)</label>
                            <input type="text" name="incisos[1][manzano]" class="form-control" value="{{ old('incisos.1.manzano', $tramite->predio->manzano ?? '23') }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Lote (Ej: 4-B) (*)</label>
                            <input type="text" name="incisos[1][lote]" class="form-control" value="{{ old('incisos.1.lote', '4-B') }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Superficie (Ej: 288.27) (*)</label>
                            <input type="number" step="0.01" name="incisos[1][superficie]" class="form-control" value="{{ old('incisos.1.superficie', '288.27') }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Nuevo Lote (Ej: 25) (*)</label>
                            <input type="text" name="incisos[1][lote_nuevo]" class="form-control" value="{{ old('incisos.1.lote_nuevo', '25') }}" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Sup. Legal % (*)</label>
                            <input type="number" step="0.01" name="incisos[1][superficie_legal_porcentaje]" class="form-control" value="{{ old('incisos.1.superficie_legal_porcentaje', '49.59') }}" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Sup. Util % (*)</label>
                            <input type="number" step="0.01" name="incisos[1][superficie_util_porcentaje]" class="form-control" value="{{ old('incisos.1.superficie_util_porcentaje', '49.59') }}" required>
                        </div>
                    </div>
                    <h6>Colindantes del Nuevo Lote</h6>
                    <div class="row">
                        <div class="col-md-3 form-group"><input type="text" name="incisos[1][col_norte]" class="form-control" placeholder="Colindante Norte (*)" required></div>
                        <div class="col-md-3 form-group"><input type="text" name="incisos[1][col_sur]" class="form-control" placeholder="Colindante Sur (*)" required></div>
                        <div class="col-md-3 form-group"><input type="text" name="incisos[1][col_este]" class="form-control" placeholder="Colindante Este (*)" required></div>
                        <div class="col-md-3 form-group"><input type="text" name="incisos[1][col_oeste]" class="form-control" placeholder="Colindante Oeste (*)" required></div>
                    </div>
                    <button type="button" class="btn btn-xs btn-danger btn-remove-inciso" style="display: none;">Quitar</button>
                    <hr>
                </div>
            </div>
        </div>

        <div class="mt-3 mb-4">
            <a href="{{ route('admin.tramites.show', $tramite) }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> Generar Resolución PDF
            </button>
        </div>
    </form>
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Inicializar Select2 en los selects existentes
        function initSelect2() {
            $('.select2-propietarios').select2({
                placeholder: "-- Seleccione --",
                allowClear: true
            });
        }
        initSelect2();

        var incisoIndex = 2; // Empezamos en 2 porque ya tenemos 0 y 1

        // Plantilla para nuevos incisos
        var incisoTemplate = `
            <div class="inciso-item" data-index="__INDEX__">
                <h5 class="text-primary">Lote Resultante #__COUNT__</h5>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Propietario Asignado (*)</label>
                        <select name="incisos[__INDEX__][propietario_id]" class="form-control select2-propietarios" required>
                            <option value="">-- Seleccione --</option>
                            @foreach($propietarios_lista as $prop)
                                <option value="{{ $prop->id }}">{{ $prop->persona->nombre_completo }} ({{ $prop->persona->carnet }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Manzano (Ej: 23) (*)</label>
                        <input type="text" name="incisos[__INDEX__][manzano]" class="form-control" value="{{ $tramite->predio->manzano ?? '23' }}" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Lote (Ej: 4-C) (*)</label>
                        <input type="text" name="incisos[__INDEX__][lote]" class="form-control" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Superficie (m²) (*)</label>
                        <input type="number" step="0.01" name="incisos[__INDEX__][superficie]" class="form-control" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Nuevo Lote (Ej: 26) (*)</label>
                        <input type="text" name="incisos[__INDEX__][lote_nuevo]" class="form-control" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Sup. Legal % (*)</label>
                        <input type="number" step="0.01" name="incisos[__INDEX__][superficie_legal_porcentaje]" class="form-control" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Sup. Util % (*)</label>
                        <input type="number" step="0.01" name="incisos[__INDEX__][superficie_util_porcentaje]" class="form-control" required>
                    </div>
                </div>
                <h6>Colindantes del Nuevo Lote</h6>
                <div class="row">
                    <div class="col-md-3 form-group"><input type="text" name="incisos[__INDEX__][col_norte]" class="form-control" placeholder="Colindante Norte (*)" required></div>
                    <div class="col-md-3 form-group"><input type="text" name="incisos[__INDEX__][col_sur]" class="form-control" placeholder="Colindante Sur (*)" required></div>
                    <div class="col-md-3 form-group"><input type="text" name="incisos[__INDEX__][col_este]" class="form-control" placeholder="Colindante Este (*)" required></div>
                    <div class="col-md-3 form-group"><input type="text" name="incisos[__INDEX__][col_oeste]" class="form-control" placeholder="Colindante Oeste (*)" required></div>
                </div>
                <button type="button" class="btn btn-xs btn-danger btn-remove-inciso">Quitar</button>
                <hr>
            </div>`;

        // Añadir un nuevo inciso
        $('#add-inciso').on('click', function() {
            var newInciso = incisoTemplate.replace(/__INDEX__/g, incisoIndex).replace(/__COUNT__/g, incisoIndex + 1);
            var $newElement = $(newInciso);
            $('#incisos-container').append($newElement);
            $newElement.find('.select2-propietarios').select2({
                placeholder: "-- Seleccione --",
                allowClear: true
            });
            incisoIndex++;
            updateRemoveButtons();
        });

        // Quitar un inciso
        $('#incisos-container').on('click', '.btn-remove-inciso', function() {
            $(this).closest('.inciso-item').remove();
            updateRemoveButtons();
        });

        // Ocultar el botón de quitar si solo hay 2
        function updateRemoveButtons() {
            var items = $('.inciso-item');
            if (items.length <= 2) {
                items.find('.btn-remove-inciso').hide();
            } else {
                items.find('.btn-remove-inciso').show();
            }
        }
    });
</script>
@stop