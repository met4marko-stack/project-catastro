@extends('adminlte::page')

@section('title', 'Crear Nuevo Usuario')

@section('content_header')
    <h1>
        <b>Creación de un Nuevo Usuario</b>
    </h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Llene los datos del formulario</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.usuarios.store') }}" method="POST">
                @csrf
                <div class="row">
                    {{-- Columna 1: Datos Personales --}}
                    <div class="col-md-6">
                        <h5>Datos Personales</h5>
                        <hr>
                        {{-- Nombre, Apellidos, etc. --}}
                        <div class="form-group"><label>Nombre(s) (*)</label><input type="text" class="form-control"
                                name="nombre" value="{{ old('nombre') }}" required></div>
                        <div class="form-group"><label>Primer Apellido (*)</label><input type="text" class="form-control"
                                name="primer_apellido" value="{{ old('primer_apellido') }}" required></div>
                        <div class="form-group"><label>Segundo Apellido</label><input type="text" class="form-control"
                                name="segundo_apellido" value="{{ old('segundo_apellido') }}"></div>

                        {{-- Fila para Carnet y Expedido --}}
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group"><label>Carnet de Identidad (*)</label><input type="text"
                                        class="form-control" name="carnet" value="{{ old('carnet') }}" required></div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group"><label>Expedido</label><select name="expedido" class="form-control">
                                        <option value="">--</option>
                                        @foreach ($expedidoOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Fila para Caducidad de Carnet --}}
                        <div class="row align-items-end">
                            <div class="col-md-8">
                                <div class="form-group"><label>Fecha de Caducidad del Carnet</label><input type="date"
                                        class="form-control" name="ci_fecha_caducidad" id="ci_fecha_caducidad"
                                        value="{{ old('ci_fecha_caducidad') }}"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox"><input class="custom-control-input"
                                            type="checkbox" id="ci_es_indefinido" name="ci_es_indefinido"><label
                                            for="ci_es_indefinido" class="custom-control-label">Indefinido</label></div>
                                </div>
                            </div>
                        </div>

                        {{-- Teléfono y Fecha de Nacimiento --}}
                        <div class="form-group"><label>Teléfono</label><input type="text" class="form-control"
                                name="telefono" value="{{ old('telefono') }}"></div>
                        <div class="form-group"><label>Fecha de Nacimiento</label><input type="date" class="form-control"
                                name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}"></div>
                    </div>

                    {{-- Columna 2: Datos de Acceso y Rol --}}
                    <div class="col-md-6">
                        <h5>Datos de Acceso y Rol</h5>
                        <hr>
                        {{-- Email, Contraseñas, Rol, Municipio --}}
                        <div class="form-group"><label>Email (*)</label><input type="email" class="form-control"
                                name="email" value="{{ old('email') }}" required></div>
                        <div class="form-group"><label>Contraseña (*)</label><input type="password" class="form-control"
                                name="password" required></div>
                        <div class="form-group"><label>Confirmar Contraseña (*)</label><input type="password"
                                class="form-control" name="password_confirmation" required></div>
                        <div class="form-group"><label>Rol del Usuario (*)</label><select name="rol_id"
                                class="form-control" required>
                                <option value="">-- Seleccione un Rol --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if (Auth::user()->hasRole('Super-Admin'))
                            <div class="form-group"><label>Municipio (*)</label><select name="municipio_id"
                                    class="form-control" required>
                                    <option value="">-- Seleccione un Municipio --</option>
                                    @foreach ($municipios as $municipio)
                                        <option value="{{ $municipio->id }}">{{ $municipio->nombre }}</option>
                                    @endforeach
                                </select></div>
                        @endif
                    </div>
                </div>
                <hr>
                <div class="form-group"><a href="{{ route('admin.usuarios.index') }}"
                        class="btn btn-secondary">Cancelar</a><button type="submit" class="btn btn-primary">Guardar
                        Usuario</button></div>
            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        // Script para deshabilitar la fecha de caducidad si el carnet es indefinido
        document.getElementById('ci_es_indefinido').addEventListener('change', function() {
            var fechaCaducidadInput = document.getElementById('ci_fecha_caducidad');
            if (this.checked) {
                fechaCaducidadInput.disabled = true;
                fechaCaducidadInput.value = ''; // Limpiar el valor
            } else {
                fechaCaducidadInput.disabled = false;
            }
        });
    </script>
@stop
