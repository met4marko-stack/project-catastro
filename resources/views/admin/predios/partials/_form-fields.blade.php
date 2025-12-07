<div class="row">
    {{-- Columna Izquierda: Datos Principales --}}
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">1. Datos de Identificación y Propietarios</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Planimetría (*)</label>
                        <select name="planimetria_id" id="planimetria_id" class="form-control select2" required>
                            <option value="">-- Seleccione una Planimetría --</option>
                            @foreach ($planimetrias as $planimetria)
                                <option value="{{ $planimetria->id }}"
                                    {{ old('planimetria_id', $predio->planimetria_id ?? '') == $planimetria->id ? 'selected' : '' }}>
                                    {{ $planimetria->codigo }} ({{ $planimetria->municipio->nombre }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Propietario(s) (*)</label>
                        <select name="propietarios[]" id="propietarios" class="form-control select2" multiple="multiple"
                            required>
                            @foreach ($propietarios as $propietario)
                                <option value="{{ $propietario->id }}"
                                    {{ in_array($propietario->id, old('propietarios', $predio->propietarios->pluck('id')->toArray() ?? [])) ? 'selected' : '' }}>
                                    {{ $propietario->persona->nombre_completo }} ({{ $propietario->persona->carnet }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group"><label>N° de Plano</label><input type="text" name="numero_plano"
                            value="{{ old('numero_plano', $predio->numero_plano ?? '') }}" class="form-control"></div>
                    <div class="col-md-4 form-group"><label>N° de Matrícula/Folio Real (*)</label><input type="text"
                            name="numero_matricula_folio"
                            value="{{ old('numero_matricula_folio', $predio->numero_matricula_folio ?? '') }}" class="form-control"
                            required></div>
                    <div class="col-md-4 form-group"><label>Código Catastral (*)</label><input type="text"
                            name="codigo_catastral"
                            value="{{ old('codigo_catastral', $predio->codigo_catastral ?? '') }}" class="form-control"
                            required></div>
                </div>
            </div>
        </div>

        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">2. Datos de Ubicación y Superficies</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group"><label>Manzano</label><input type="text" name="manzano"
                            value="{{ old('manzano', $predio->manzano ?? '') }}" class="form-control"></div>
                    <div class="col-md-4 form-group"><label>Lote</label><input type="text" name="lote"
                            value="{{ old('lote', $predio->lote ?? '') }}" class="form-control"></div>
                    <div class="col-md-4 form-group"><label>Zona/Urbanización</label><input type="text"
                            name="zona" value="{{ old('zona', $predio->zona ?? '') }}" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Provincia</label>
                        <select name="provincia_id" class="form-control select2">
                            <option value="">-- Seleccione una Provincia --</option>
                            @foreach ($provincias as $provincia)
                                <option value="{{ $provincia->id }}"
                                    {{ old('provincia_id', $predio->provincia_id ?? '') == $provincia->id ? 'selected' : '' }}>
                                    {{ $provincia->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Centro Poblado</label>
                        <select name="centro_poblado_id" class="form-control select2">
                            <option value="">-- Seleccione un Centro Poblado --</option>
                            @foreach ($centrosPoblados as $centro)
                                <option value="{{ $centro->id }}"
                                    {{ old('centro_poblado_id', $predio->centro_poblado_id ?? '') == $centro->id ? 'selected' : '' }}>
                                    {{ $centro->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-4 form-group"><label>Superficie s/Levantamiento (m²)</label><input type="number"
                            step="0.01" name="sup_levantamiento"
                            value="{{ old('sup_levantamiento', $predio->sup_levantamiento ?? '') }}"
                            class="form-control"></div>
                    <div class="col-md-4 form-group"><label>Superficie s/Testimonio (m²)</label><input type="number"
                            step="0.01" name="sup_testimonio"
                            value="{{ old('sup_testimonio', $predio->sup_testimonio ?? '') }}" class="form-control">
                    </div>
                    <div class="col-md-4 form-group"><label>Superficie Construida (m²)</label><input type="number"
                            step="0.01" name="sup_construida"
                            value="{{ old('sup_construida', $predio->sup_construida ?? '') }}" class="form-control">
                    </div>
                    <div class="col-md-6 form-group"><label>Superficie Afectada (m²)</label><input type="number"
                            step="0.01" name="sup_afectada"
                            value="{{ old('sup_afectada', $predio->sup_afectada ?? '') }}" class="form-control"></div>
                    <div class="col-md-6 form-group"><label>Superficie Útil (m²)</label><input type="number"
                            step="0.01" name="sup_util" value="{{ old('sup_util', $predio->sup_util ?? '') }}"
                            class="form-control"></div>
                </div>
            </div>
        </div>

        <script src="//unpkg.com/alpinejs" defer></script>
        
        <div class="card card-secondary" x-data="colindanciasApp()">
            <div class="card-header">
                <h3 class="card-title">3. Colindancias y Características</h3>
            </div>
            <div class="card-body">
                <label>Colindancias</label>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 20%">Orientación</th>
                                <th style="width: 20%">Tipo</th>
                                <th>Descripción (Vía o Nombre/Número)</th>
                                <th style="width: 50px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(col, index) in colindancias" :key="index">
                                <tr>
                                    <td>
                                        <select :name="'colindancias['+index+'][orientacion_id]'" class="form-control form-control-sm" x-model="col.orientacion_id" required>
                                            <option value="">--</option>
                                            @foreach($orientaciones as $o) <option value="{{$o->id}}">{{$o->nombre}}</option> @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select :name="'colindancias['+index+'][tipo_colindante_id]'" class="form-control form-control-sm" x-model="col.tipo_colindante_id" required>
                                            <option value="">--</option>
                                            @foreach($tiposColindante as $t) <option value="{{$t->id}}">{{$t->nombre}}</option> @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <!-- Input condicional -->
                                        <template x-if="col.tipo_colindante_id == '{{ $tiposColindante->firstWhere('nombre', 'VIA')->id }}'">
                                            <select :name="'colindancias['+index+'][via_id]'" class="form-control form-control-sm" x-model="col.via_id">
                                                <option value="">-- Seleccione Vía --</option>
                                                @foreach($vias as $v) <option value="{{$v->id}}">{{$v->nombre}}</option> @endforeach
                                            </select>
                                        </template>
                                        <template x-if="col.tipo_colindante_id != '{{ $tiposColindante->firstWhere('nombre', 'VIA')->id }}'">
                                            <input type="text" :name="'colindancias['+index+'][nombre_o_numero]'" class="form-control form-control-sm" x-model="col.nombre_o_numero" placeholder="Ej: 12, Rio...">
                                        </template>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-xs" @click="remove(index)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success btn-sm" @click="add()"><i class="fas fa-plus"></i> Agregar Colindancia</button>
                </div>
                <hr>
                <script>
                    function colindanciasApp() {
                        return {
                            colindancias: {!! isset($predio) && $predio->exists ? $predio->colindancias->toJson() : '[]' !!},
                            add() {
                                this.colindancias.push({ orientacion_id: '', tipo_colindante_id: '', via_id: '', nombre_o_numero: '' });
                            },
                            remove(index) {
                                this.colindancias.splice(index, 1);
                            }
                        }
                    }
                </script>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Frente Principal (m)</label>
                        <input type="number" step="0.01" name="frente_principal"
                            value="{{ old('frente_principal', $predio->frente_principal ?? '') }}"
                            class="form-control">
                    </div>
                    {{-- CAMBIO: Campo de texto a Select para Material de Vía --}}
                    <div class="col-md-4 form-group">
                        <label>Material de Vía</label>
                        <select name="id_material_via" class="form-control select2">
                            <option value="">-- Seleccione --</option>
                            {{-- Asumiendo que pasas $materialesVias desde el controlador --}}
                            @foreach ($materialesVias as $material)
                                <option value="{{ $material->id }}"
                                    {{ old('id_material_via', $predio->id_material_via ?? '') == $material->id ? 'selected' : '' }}>
                                    {{ $material->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CAMBIO: Campo de texto a Select para Forma del Lote (Booleano) --}}
                    <div class="col-md-4 form-group">
                        <label>Forma del Lote</label>
                        <select name="forma_lote" class="form-control select2">
                            <option value="">-- Seleccione --</option>
                            <option value="Regular"
                                {{ old('forma_lote', isset($predio) && $predio->forma_lote ? 'Regular' : '') == 'Regular' ? 'selected' : '' }}>
                                Regular
                            </option>
                            <option value="Irregular"
                                {{ old('forma_lote', isset($predio) && !$predio->forma_lote && !is_null($predio->forma_lote) ? 'Irregular' : '') == 'Irregular' ? 'selected' : '' }}>
                                Irregular
                            </option>
                        </select>
                    </div>
                </div>
                {{-- AÑADIR: Nuevo campo para seleccionar la Vía --}}
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>Vía a la que pertenece</label>
                        <select name="via_id" class="form-control select2">
                            <option value="">-- Seleccione una vía --</option>
                            {{-- Asumiendo que pasas $vias desde el controlador --}}
                            @foreach ($vias as $via)
                                <option value="{{ $via->id }}"
                                    {{ old('via_id', $predio->via_id ?? '') == $via->id ? 'selected' : '' }}>
                                    {{ $via->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- Columna Derecha: Datos Secundarios --}}
    <div class="col-md-4">
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">4. Propiedad Horizontal</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                        <input type="checkbox" class="custom-control-input" id="propiedad_horizontal"
                            name="propiedad_horizontal" value="1"
                            {{ old('propiedad_horizontal', $predio->propiedad_horizontal ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="propiedad_horizontal">Es un Inmueble Padre
                            (Edificio)</label>
                    </div>
                </div>
                <div id="campos_unidad"
                    style="{{ old('propiedad_horizontal', $predio->propiedad_horizontal ?? false) ? 'display:none;' : '' }}">
                    <div class="form-group">
                        <label>Inmueble Padre al que pertenece</label>
                        <select name="inmueble_padre_id" class="form-control select2">
                            <option value="">-- No es una unidad / Seleccionar --</option>
                            @foreach ($prediosPadre as $padre)
                                <option value="{{ $padre->id }}"
                                    {{ old('inmueble_padre_id', $predio->inmueble_padre_id ?? '') == $padre->id ? 'selected' : '' }}>
                                    {{ $padre->codigo_catastral }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Número de Unidad / Departamento</label>
                        <input type="text" name="numero_unidad"
                            value="{{ old('numero_unidad', $predio->numero_unidad ?? '') }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">5. Servicios Básicos</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="custom-control custom-checkbox"><input class="custom-control-input" type="checkbox"
                            id="agua_potable" name="agua_potable" value="1"
                            {{ old('agua_potable', $predio->agua_potable ?? false) ? 'checked' : '' }}><label
                            for="agua_potable" class="custom-control-label">Agua Potable</label></div>
                    <div class="custom-control custom-checkbox"><input class="custom-control-input" type="checkbox"
                            id="energia_electrica" name="energia_electrica" value="1"
                            {{ old('energia_electrica', $predio->energia_electrica ?? false) ? 'checked' : '' }}><label
                            for="energia_electrica" class="custom-control-label">Energía Eléctrica</label></div>
                    <div class="custom-control custom-checkbox"><input class="custom-control-input" type="checkbox"
                            id="alcantarillado" name="alcantarillado" value="1"
                            {{ old('alcantarillado', $predio->alcantarillado ?? false) ? 'checked' : '' }}><label
                            for="alcantarillado" class="custom-control-label">Alcantarillado</label></div>
                    <div class="custom-control custom-checkbox"><input class="custom-control-input" type="checkbox"
                            id="alumbrado_publico" name="alumbrado_publico" value="1"
                            {{ old('alumbrado_publico', $predio->alumbrado_publico ?? false) ? 'checked' : '' }}><label
                            for="alumbrado_publico" class="custom-control-label">Alumbrado Público</label></div>
                    <div class="custom-control custom-checkbox"><input class="custom-control-input" type="checkbox"
                            id="gas_domiciliario" name="gas_domiciliario" value="1"
                            {{ old('gas_domiciliario', $predio->gas_domiciliario ?? false) ? 'checked' : '' }}><label
                            for="gas_domiciliario" class="custom-control-label">Gas Domiciliario</label></div>
                </div>
            </div>
        </div>

        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">6. Fotografías (Opcional, máx. 5)</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="fotografias">Cargar una o varias imágenes</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" name="fotografias[]" id="fotografias"
                            multiple accept="image/*">
                        <label class="custom-file-label" for="fotografias">Elegir archivos...</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Campo oculto para las coordenadas --}}
<!--<textarea name="coordenadas_text" id="coordenadas_text" style="display: none;">{{ old('coordenadas_text', '[]') }}</textarea>-->
