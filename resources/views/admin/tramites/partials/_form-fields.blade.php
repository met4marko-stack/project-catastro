{{-- 
    Este formulario parcial espera una variable $tramite.
    En la vista 'create', se pasa un 'new Tramite()'.
    En la vista 'edit', se pasa el trámite existente.
--}}


<input type="hidden" name="solicitante_id" id="solicitante_id_hidden" value="{{ old('solicitante_id', $tramite->solicitante_id) }}" required>

<div class="row">
    <div class="col-md-6 form-group">
        <label>Tipo de Trámite (*)</label>
        <select name="tramite_tipo_id" id="tramite_tipo_id" class="form-control select2" required>
            <option value="">-- Seleccione el tipo de trámite --</option>
            @foreach ($tipos_de_tramite as $tipo)
                <option value="{{ $tipo->id }}" {{ old('tramite_tipo_id', $tramite->tramite_tipo_id) == $tipo->id ? 'selected' : '' }}>
                    {{ $tipo->nombre }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 form-group">
        <label>Predio (por Código Catastral) (*)</label>
        <select name="predio_id" id="predio_id" class="form-control select2" required>
            <option value="">-- Seleccione el predio --</option>
            @foreach ($predios as $predio)
                <option value="{{ $predio->id }}" {{ old('predio_id', $tramite->predio_id) == $predio->id ? 'selected' : '' }}>
                    {{ $predio->codigo_catastral }}
                </option>
            @endforeach
        </select>
    </div>
</div>

{{-- --- INICIO: NUEVA SECCIÓN DE SOLICITANTE CON RADIO BUTTONS --- --}}
<div class="form-group">
    <label>¿Quién realiza el trámite? (*)</label>
    <div class="d-flex">
        <div class="custom-control custom-radio mr-3">
            <input type="radio" id="tipo_solicitante_propietario" name="solicitante_tipo" class="custom-control-input" value="propietario" disabled>
            <label class="custom-control-label" for="tipo_solicitante_propietario">El Propietario</label>
        </div>
        <div class="custom-control custom-radio">
            <input type="radio" id="tipo_solicitante_apoderado" name="solicitante_tipo" class="custom-control-input" value="apoderado" disabled>
            <label class="custom-control-label" for="tipo_solicitante_apoderado">Un Apoderado</label>
        </div>
    </div>
    <small class="form-text text-muted">Seleccione un predio para habilitar esta opción.</small>
</div>

{{-- Contenedor para el dropdown de Propietarios (se llena con JS) --}}
<div class="form-group" id="propietario_select_wrapper" style="display: none;">
    <label>Seleccione el Propietario (*)</label>
    <select id="solicitante_id_propietario" class="form-control select2-propietarios">
        {{-- Las opciones se cargarán vía AJAX --}}
    </select>
</div>

{{-- Contenedor para el dropdown de Apoderados (se llena con JS) --}}
<div id="apoderado_select_wrapper" style="display: none;">
    <div class="form-group">
        <label>Buscar Apoderado (*)</label>
        <div class="input-group">
            <select id="solicitante_id_apoderado" class="form-control select2-apoderados">
                {{-- Las opciones se cargarán vía AJAX --}}
            </select>
            <div class="input-group-append">
                <button class="btn btn-outline-primary" type="button" data-toggle="collapse" data-target="#collapseNuevoApoderado" aria-expanded="false" aria-controls="collapseNuevoApoderado">
                    <i class="fas fa-plus"></i> Nuevo
                </button>
            </div>
        </div>
    </div>

    {{-- Formulario colapsable para registrar nuevo apoderado --}}
    <div class="collapse" id="collapseNuevoApoderado">
        <div class="card card-body bg-light mb-3">
            <h5>Registrar Nuevo Solicitante (Apoderado)</h5>
            <div id="form-nuevo-apoderado">
                <div class="row">
                    <div class="col-md-4 form-group"><label>Nombre(s) (*)</label><input type="text" id="apoderado_nombre" class="form-control"></div>
                    <div class="col-md-4 form-group"><label>Primer Apellido (*)</label><input type="text" id="apoderado_primer_apellido" class="form-control"></div>
                    <div class="col-md-4 form-group"><label>Segundo Apellido</label><input type="text" id="apoderado_segundo_apellido" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-8 form-group"><label>Carnet de Identidad (*)</label><input type="text" id="apoderado_carnet" class="form-control"></div>
                    <div class="col-md-4 form-group">
                        <label>Expedido (*)</label>
                        <select id="apoderado_expedido" class="form-control">
                            <option value="">--</option>
                            @foreach($expedidoOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="apoderado-errors" class="alert alert-danger" style="display: none;"></div>
                <button type="button" id="btn-guardar-apoderado" class="btn btn-success">Guardar y Seleccionar</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label>Fecha de Ingreso (*)</label>
        <input type="date" name="fecha_ingreso" class="form-control" value="{{ old('fecha_ingreso', optional($tramite->fecha_ingreso)->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-6 form-group">
        <label>Hoja de Ruta</label>
        <input type="text" name="hoja_ruta" class="form-control" value="{{ old('hoja_ruta', $tramite->hoja_ruta) }}">
    </div>
</div>

<div class="form-group">
    <label>Observaciones Iniciales</label>
    <textarea name="observaciones" class="form-control" rows="3">{{ old('observaciones', $tramite->observaciones) }}</textarea>
</div>