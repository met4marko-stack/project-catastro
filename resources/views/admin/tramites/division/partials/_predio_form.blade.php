<div class="card card-info card-outline predio-card">
    <div class="card-header">
        <h3 class="card-title">Predio Resultante #<span class="predio-number">{{ $loop_iteration ?? (is_numeric($index) ? $index + 1 : 'TEMPLATE_LABEL') }}</span></h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            @if(isset($is_template) && $is_template)
                <button type="button" class="btn btn-tool btn-remove-predio"><i class="fas fa-times"></i></button>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Columna Izquierda: Datos Principales --}}
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Datos de Identificación y Propietarios</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Planimetría (*)</label>
                                <select name="predios[{{ $index }}][planimetria_id]" class="form-control select2-dynamic" required>
                                    <option value="">-- Seleccione --</option>
                                    @foreach ($planimetrias as $planimetria)
                                        <option value="{{ $planimetria->id }}"
                                            {{ (old("predios.$index.planimetria_id") ?? ($predioOriginal->planimetria_id ?? '')) == $planimetria->id ? 'selected' : '' }}>
                                            {{ $planimetria->codigo }} ({{ $planimetria->municipio->nombre }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Propietario(s) (*)</label>
                                <select name="predios[{{ $index }}][propietarios][]" class="form-control select2-dynamic" multiple="multiple" required>
                                    @foreach ($propietarios as $propietario)
                                        <option value="{{ $propietario->id }}"
                                            {{ (collect(old("predios.$index.propietarios"))->contains($propietario->id)) ? 'selected' : '' }}>
                                            {{ $propietario->persona->nombre_completo }} ({{ $propietario->persona->carnet }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group"><label>N° de Plano</label>
                                <input type="text" name="predios[{{ $index }}][numero_plano]" value="{{ old("predios.$index.numero_plano") }}" class="form-control">
                            </div>
                            <div class="col-md-4 form-group"><label>N° de Matrícula/Folio Real (*)</label>
                                <input type="text" name="predios[{{ $index }}][numero_matricula_folio]" value="{{ old("predios.$index.numero_matricula_folio") }}" class="form-control" required>
                            </div>
                            <div class="col-md-4 form-group"><label>Código Catastral (*)</label>
                                <input type="text" name="predios[{{ $index }}][codigo_catastral]" value="{{ old("predios.$index.codigo_catastral") }}" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Datos de Ubicación y Superficies</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 form-group"><label>Manzano</label>
                                <input type="text" name="predios[{{ $index }}][manzano]" value="{{ old("predios.$index.manzano") }}" class="form-control input-manzano">
                            </div>
                            <div class="col-md-3 form-group"><label>Lote (Numérico)</label>
                                <input type="text" name="predios[{{ $index }}][lote]" value="{{ old("predios.$index.lote") }}" class="form-control input-lote">
                            </div>
                            {{-- NUEVO CAMPO DENOMINATIVO --}}
                            <div class="col-md-3 form-group">
                                <label>Denominativo PDF</label>
                                <input type="text" name="predios[{{ $index }}][denominativo]" value="{{ old("predios.$index.denominativo") }}" class="form-control" placeholder="Ej. LOTE 4-A">
                                <small class="text-muted">Sobrescribe al Lote</small>
                            </div>
                            <div class="col-md-3 form-group"><label>Zona</label>
                                <input type="text" name="predios[{{ $index }}][zona]" value="{{ old("predios.$index.zona") ?? ($predioOriginal->zona ?? '') }}" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Provincia</label>
                                <select name="predios[{{ $index }}][provincia_id]" class="form-control select2-dynamic">
                                    <option value="">-- Seleccione --</option>
                                    @foreach ($provincias as $provincia)
                                        <option value="{{ $provincia->id }}" {{ (old("predios.$index.provincia_id") ?? ($predioOriginal->provincia_id ?? '')) == $provincia->id ? 'selected' : '' }}>
                                            {{ $provincia->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Centro Poblado</label>
                                <select name="predios[{{ $index }}][centro_poblado_id]" class="form-control select2-dynamic">
                                    <option value="">-- Seleccione --</option>
                                    @foreach ($centrosPoblados as $centro)
                                        <option value="{{ $centro->id }}" {{ (old("predios.$index.centro_poblado_id") ?? ($predioOriginal->centro_poblado_id ?? '')) == $centro->id ? 'selected' : '' }}>
                                            {{ $centro->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4 form-group"><label>Sup. Levantamiento (m²)</label><input type="number" step="0.01" name="predios[{{ $index }}][sup_levantamiento]" value="{{ old("predios.$index.sup_levantamiento") }}" class="form-control"></div>
                            <div class="col-md-4 form-group"><label>Sup. Testimonio (m²)</label><input type="number" step="0.01" name="predios[{{ $index }}][sup_testimonio]" value="{{ old("predios.$index.sup_testimonio") }}" class="form-control"></div>
                            <div class="col-md-4 form-group"><label>Sup. Construida (m²)</label><input type="number" step="0.01" name="predios[{{ $index }}][sup_construida]" value="{{ old("predios.$index.sup_construida") }}" class="form-control"></div>
                            <div class="col-md-6 form-group"><label>Sup. Afectada (m²)</label><input type="number" step="0.01" name="predios[{{ $index }}][sup_afectada]" value="{{ old("predios.$index.sup_afectada") }}" class="form-control"></div>
                            <div class="col-md-6 form-group"><label>Sup. Útil (m²)</label><input type="number" step="0.01" name="predios[{{ $index }}][sup_util]" value="{{ old("predios.$index.sup_util") }}" class="form-control"></div>
                        </div>
                    </div>
                </div>

                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Colindantes y Características</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Norte</label><input type="text" name="predios[{{ $index }}][colindante_norte]" value="{{ old("predios.$index.colindante_norte") }}" class="form-control"></div>
                            <div class="col-md-6 form-group"><label>Sur</label><input type="text" name="predios[{{ $index }}][colindante_sur]" value="{{ old("predios.$index.colindante_sur") }}" class="form-control"></div>
                            <div class="col-md-6 form-group"><label>Este</label><input type="text" name="predios[{{ $index }}][colindante_este]" value="{{ old("predios.$index.colindante_este") }}" class="form-control"></div>
                            <div class="col-md-6 form-group"><label>Oeste</label><input type="text" name="predios[{{ $index }}][colindante_oeste]" value="{{ old("predios.$index.colindante_oeste") }}" class="form-control"></div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>Frente Principal (m)</label>
                                <input type="number" step="0.01" name="predios[{{ $index }}][frente_principal]" value="{{ old("predios.$index.frente_principal") }}" class="form-control">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Material de Vía</label>
                                <select name="predios[{{ $index }}][id_material_via]" class="form-control select2-dynamic">
                                    <option value="">-- Seleccione --</option>
                                    @foreach ($materialesVias as $material)
                                        <option value="{{ $material->id }}" {{ (old("predios.$index.id_material_via") ?? ($predioOriginal->id_material_via ?? '')) == $material->id ? 'selected' : '' }}>
                                            {{ $material->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Forma del Lote</label>
                                <select name="predios[{{ $index }}][forma_lote]" class="form-control select2-dynamic">
                                    <option value="Regular" {{ (old("predios.$index.forma_lote") == 'Regular') ? 'selected' : '' }}>Regular</option>
                                    <option value="Irregular" {{ (old("predios.$index.forma_lote") == 'Irregular') ? 'selected' : '' }}>Irregular</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                             <div class="col-md-12 form-group">
                                <label>Vía</label>
                                <select name="predios[{{ $index }}][via_id]" class="form-control select2-dynamic">
                                    <option value="">-- Seleccione --</option>
                                    @foreach ($vias as $via)
                                        <option value="{{ $via->id }}" {{ (old("predios.$index.via_id") ?? ($predioOriginal->via_id ?? '')) == $via->id ? 'selected' : '' }}>
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
                        <h3 class="card-title">Servicios Básicos</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="agua_potable_{{ $index }}" name="predios[{{ $index }}][agua_potable]" value="1" {{ (old("predios.$index.agua_potable") ?? ($predioOriginal->agua_potable ?? false)) ? 'checked' : '' }}>
                                <label for="agua_potable_{{ $index }}" class="custom-control-label">Agua Potable</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="energia_electrica_{{ $index }}" name="predios[{{ $index }}][energia_electrica]" value="1" {{ (old("predios.$index.energia_electrica") ?? ($predioOriginal->energia_electrica ?? false)) ? 'checked' : '' }}>
                                <label for="energia_electrica_{{ $index }}" class="custom-control-label">Energía Eléctrica</label>
                            </div>
                             <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="alcantarillado_{{ $index }}" name="predios[{{ $index }}][alcantarillado]" value="1" {{ (old("predios.$index.alcantarillado") ?? ($predioOriginal->alcantarillado ?? false)) ? 'checked' : '' }}>
                                <label for="alcantarillado_{{ $index }}" class="custom-control-label">Alcantarillado</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="alumbrado_publico_{{ $index }}" name="predios[{{ $index }}][alumbrado_publico]" value="1" {{ (old("predios.$index.alumbrado_publico") ?? ($predioOriginal->alumbrado_publico ?? false)) ? 'checked' : '' }}>
                                <label for="alumbrado_publico_{{ $index }}" class="custom-control-label">Alumbrado Público</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="gas_domiciliario_{{ $index }}" name="predios[{{ $index }}][gas_domiciliario]" value="1" {{ (old("predios.$index.gas_domiciliario") ?? ($predioOriginal->gas_domiciliario ?? false)) ? 'checked' : '' }}>
                                <label for="gas_domiciliario_{{ $index }}" class="custom-control-label">Gas Domiciliario</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>