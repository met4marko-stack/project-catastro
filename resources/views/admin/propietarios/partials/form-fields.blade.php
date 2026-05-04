<div class="row">
    <div class="col-md-6">
        <h5>Datos Personales</h5>
        <hr>
        <div class="form-group">
            <label>Nombre(s) (*)</label>
            <input type="text" class="form-control" name="nombre" value="{{ old('nombre', $propietario->persona->nombre ?? '') }}" required>
        </div>
        <div class="form-group">
            <label>Primer Apellido (*)</label>
            <input type="text" class="form-control" name="primer_apellido" value="{{ old('primer_apellido', $propietario->persona->primer_apellido ?? '') }}" required>
        </div>
        <div class="form-group">
            <label>Segundo Apellido</label>
            <input type="text" class="form-control" name="segundo_apellido" value="{{ old('segundo_apellido', $propietario->persona->segundo_apellido ?? '') }}">
        </div>
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label>Carnet de Identidad (*)</label>
                    <input type="text" class="form-control" name="carnet" value="{{ old('carnet', $propietario->persona->carnet ?? '') }}" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Expedido</label>
                    <select name="expedido" class="form-control">
                        <option value="">--</option>
                        @foreach($expedidoOptions as $option)
                            <option value="{{ $option }}" {{ (old('expedido', $propietario->persona->expedido ?? '') == $option) ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        
        {{-- Fila para Caducidad de Carnet --}}
        <div class="row align-items-end">
            <div class="col-md-8">
                <div class="form-group">
                    <label>Fecha de Caducidad del Carnet</label>
                    <input type="date" class="form-control" name="ci_fecha_caducidad" id="ci_fecha_caducidad" value="{{ old('ci_fecha_caducidad', $propietario->persona->ci_fecha_caducidad ?? '') }}">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input" type="checkbox" id="ci_es_indefinido" name="ci_es_indefinido" value="1" {{ old('ci_es_indefinido', $propietario->persona->ci_es_indefinido ?? false) ? 'checked' : '' }}>
                        <label for="ci_es_indefinido" class="custom-control-label">Indefinido</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Teléfono</label>
            <input type="text" class="form-control" name="telefono" value="{{ old('telefono', $propietario->persona->telefono ?? '') }}">
        </div>
        <div class="form-group">
            <label>Fecha de Nacimiento</label>
            <input type="date" class="form-control" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', $propietario->persona->fecha_nacimiento ?? '') }}">
        </div>
    </div>

    <div class="col-md-6">
        <h5>Datos Administrativos</h5>
        <hr>
        @if(Auth::user()->hasRole('Super-Admin'))
            <div class="form-group">
                <label>Municipio (*)</label>
                <select name="municipio_id" class="form-control" required>
                    <option value="">-- Seleccione un Municipio --</option>
                    @foreach($municipios as $municipio)
                        <option value="{{ $municipio->id }}" {{ (old('municipio_id', $propietario->municipio_id ?? '') == $municipio->id) ? 'selected' : '' }}>{{ $municipio->nombre }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        
        @isset($propietario)
        <div class="form-group">
            <label>Estado (*)</label>
            <select name="estado" class="form-control" required>
                <option value="1" {{ (old('estado', $propietario->estado) == 1) ? 'selected' : '' }}>Activo</option>
                <option value="0" {{ (old('estado', $propietario->estado) == 0) ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>
        @endisset
    </div>
</div>

