@extends('adminlte::page')

@section('title', 'Generar Resolución de Fusión')
@section('plugins.Select2', true)
@section('plugins.Sweetalert2', true)

@section('content_header')
    <h1>Generar Resolución de Fusión (Trámite #{{ $tramite->id }})</h1>
@stop

@section('css')
<style>
    /* Hack para validación HTML5 en selects de Select2 */
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
    <form action="{{ route('admin.tramites.generateFusion', $tramite) }}" method="POST" id="fusionForm">
        @csrf
        
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Datos del Predio Base (El del trámite) --}}
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">1. Predio Base (Origen del Trámite)</h3>
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
                <div class="alert alert-warning mt-2 mb-0 p-2">
                    <i class="fas fa-exclamation-triangle"></i> Este predio será dado de baja al generar la resolución.
                </div>
            </div>
        </div>

        {{-- Datos Legales --}}
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">2. Datos del Documento Legal</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>N° de Testimonio (*)</label>
                        <input type="text" class="form-control" name="testimonio_numero" value="{{ old('testimonio_numero', '1459/2024') }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Fecha de Testimonio (*)</label>
                        <input type="date" class="form-control" name="testimonio_fecha" value="{{ old('testimonio_fecha', now()->format('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- Predios a Anexar --}}
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">3. Seleccionar Otros Predios a Fusionar/Anexar</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Buscar y Seleccionar Predios (*)</label>
                    <select name="predios_anexados[]" class="form-control select2-multiple" multiple="multiple" required>
                        @foreach($prediosDisponibles as $predio)
                            <option value="{{ $predio->id }}">
                                {{ $predio->codigo_catastral }} - {{ $predio->propietarios->first()->persona->nombre_completo ?? 'S/P' }} ({{ $predio->sup_levantamiento }} m²)
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Seleccione uno o más predios que se unirán al predio base. Estos también serán dados de baja.</small>
                </div>
            </div>
        </div>

        {{-- Predio Resultante --}}
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">4. Seleccionar Predio Resultante (Fusionado)</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Predio Nuevo (Resultado de la fusión) (*)</label>
                    <select name="predio_resultante_id" class="form-control select2-single" required>
                        <option value="">-- Seleccione el predio final --</option>
                        @foreach($prediosDisponibles as $predio)
                            <option value="{{ $predio->id }}">
                                {{ $predio->codigo_catastral }} - {{ $predio->propietarios->first()->persona->nombre_completo ?? 'S/P' }} ({{ $predio->sup_levantamiento }} m²)
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Este predio debe haber sido creado previamente en el sistema con los datos finales.</small>
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
        $('.select2-multiple').select2({
            placeholder: "Seleccione los predios a anexar",
            allowClear: true
        });
        
        $('.select2-single').select2({
            placeholder: "Seleccione el predio resultante",
            allowClear: true
        });

        // VALIDACIÓN Y SWEETALERT
        $('#btnGenerarPdf').click(function() {
            var form = document.getElementById('fusionForm');

            // 1. Validación Nativa
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // 2. Confirmación
            Swal.fire({
                title: '¿Generar Resolución?',
                text: "Se desactivarán los predios de origen (base y anexados) y se generará el documento PDF. Esta acción es irreversible.",
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
    });
</script>
@stop