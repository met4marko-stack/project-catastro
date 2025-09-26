@extends('adminlte::page')

@section('title', 'Editar Usuario')

@section('content_header')
    <h1>
        <b>Editar Usuario</b>
    </h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifique los datos del formulario</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.usuarios.update', $usuario) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    {{-- Columna 1: Datos Personales --}}
                    <div class="col-md-6">
                        <h5>Datos Personales</h5>
                        <hr>
                        <div class="form-group"><label>Nombre(s) (*)</label><input type="text" class="form-control"
                                name="nombre" value="{{ old('nombre', $usuario->persona->nombre) }}" required></div>
                        <div class="form-group"><label>Primer Apellido (*)</label><input type="text" class="form-control"
                                name="primer_apellido"
                                value="{{ old('primer_apellido', $usuario->persona->primer_apellido) }}" required></div>
                        <div class="form-group"><label>Segundo Apellido</label><input type="text" class="form-control"
                                name="segundo_apellido"
                                value="{{ old('segundo_apellido', $usuario->persona->segundo_apellido) }}"></div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group"><label>Carnet de Identidad (*)</label><input type="text"
                                        class="form-control" name="carnet"
                                        value="{{ old('carnet', $usuario->persona->carnet) }}" required></div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group"><label>Expedido</label><select name="expedido" class="form-control">
                                        <option value="">--</option>
                                        @foreach ($expedidoOptions as $option)
                                            <option value="{{ $option }}"
                                                {{ old('expedido', $usuario->persona->expedido) == $option ? 'selected' : '' }}>
                                                {{ $option }}</option>
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
                                        value="{{ old('ci_fecha_caducidad', $usuario->persona->ci_fecha_caducidad) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox"><input class="custom-control-input"
                                            type="checkbox" id="ci_es_indefinido" name="ci_es_indefinido"
                                            {{ old('ci_es_indefinido', $usuario->persona->ci_es_indefinido) ? 'checked' : '' }}><label
                                            for="ci_es_indefinido" class="custom-control-label">Indefinido</label></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group"><label>Teléfono</label><input type="text" class="form-control"
                                name="telefono" value="{{ old('telefono', $usuario->persona->telefono) }}"></div>
                        <div class="form-group"><label>Fecha de Nacimiento</label><input type="date" class="form-control"
                                name="fecha_nacimiento"
                                value="{{ old('fecha_nacimiento', $usuario->persona->fecha_nacimiento) }}"></div>
                    </div>

                    {{-- Columna 2: Datos de Acceso y Rol --}}
                    <div class="col-md-6">
                        <h5>Datos de Acceso y Rol</h5>
                        <hr>
                        <div class="form-group"><label>Email (*)</label><input type="email" class="form-control"
                                name="email" value="{{ old('email', $usuario->email) }}" required></div>
                        <div class="form-group"><label>Nueva Contraseña (Opcional)</label><input type="password"
                                class="form-control" name="password"><small class="form-text text-muted">Deje en blanco para
                                no cambiar la contraseña.</small></div>
                        <div class="form-group"><label>Confirmar Nueva Contraseña</label><input type="password"
                                class="form-control" name="password_confirmation"></div>
                        <div class="form-group"><label>Rol del Usuario (*)</label><select name="rol_id"
                                class="form-control" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}"
                                        {{ $usuario->hasRole($role->name) ? 'selected' : '' }}>{{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group"><label>Municipio (*)</label>
                            @if (Auth::user()->hasRole('Super-Admin'))
                                <select name="municipio_id" class="form-control" required>
                                    @foreach ($municipios as $municipio)
                                        <option value="{{ $municipio->id }}"
                                            {{ old('municipio_id', $usuario->municipio_id) == $municipio->id ? 'selected' : '' }}>
                                            {{ $municipio->nombre }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control"
                                    value="{{ $usuario->municipio->nombre ?? 'N/A' }}" disabled>
                            @endif
                        </div>
                    </div>
                </div>
                <hr>
                <div class="form-group"><a href="{{ route('admin.usuarios.index') }}"
                        class="btn btn-secondary">Cancelar</a><button type="submit" class="btn btn-primary">Actualizar
                        Usuario</button></div>
            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        // Función para manejar el estado del campo de fecha de caducidad
        function handleCiExpiration() {
            var checkbox = document.getElementById('ci_es_indefinido');
            var fechaCaducidadInput = document.getElementById('ci_fecha_caducidad');

            if (checkbox.checked) {
                fechaCaducidadInput.disabled = true;
                fechaCaducidadInput.value = ''; // Limpiar el valor
            } else {
                fechaCaducidadInput.disabled = false;
            }
        }

        // Ejecutar la función al cargar la página para establecer el estado inicial
        document.addEventListener('DOMContentLoaded', handleCiExpiration);

        // Ejecutar la función cada vez que el checkbox cambie
        document.getElementById('ci_es_indefinido').addEventListener('change', handleCiExpiration);
    </script>
@stop
