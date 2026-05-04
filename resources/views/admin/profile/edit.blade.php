@extends('adminlte::page')

@section('title', 'Mi Perfil')

@section('content_header')
    <h1>
        <b>Editar Mi Perfil</b>
    </h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifique sus datos personales y de acceso</h3>
        </div>
        <div class="card-body">
            {{-- Mensaje de éxito --}}
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Mensajes de error de validación --}}
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
            
            <form action="{{ route('admin.profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    {{-- Columna 1: Datos Personales --}}
                    <div class="col-md-6">
                        <h5>Datos Personales</h5>
                        <hr>
                        <div class="form-group">
                            <label>Nombre(s) (*)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user text-lightblue"></i></span>
                                </div>
                                <input type="text" class="form-control"
                                    name="nombre" value="{{ old('nombre', $user->persona->nombre) }}" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Primer Apellido (*)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user text-lightblue"></i></span>
                                </div>
                                <input type="text" class="form-control"
                                    name="primer_apellido"
                                    value="{{ old('primer_apellido', $user->persona->primer_apellido) }}" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Segundo Apellido</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user text-lightblue"></i></span>
                                </div>
                                <input type="text" class="form-control"
                                    name="segundo_apellido"
                                    value="{{ old('segundo_apellido', $user->persona->segundo_apellido) }}">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Carnet de Identidad (*)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-id-card text-lightblue"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="carnet"
                                            value="{{ old('carnet', $user->persona->carnet) }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Expedido (*)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-map-marker-alt text-lightblue"></i></span>
                                        </div>
                                        <select name="expedido" class="form-control" required>
                                            @php $expedidos = ['LP', 'CH', 'CB', 'OR', 'PT', 'TJ', 'SC', 'BE', 'PA', 'QR']; @endphp
                                            <option value="">--</option>
                                            @foreach ($expedidos as $option)
                                                <option value="{{ $option }}"
                                                    {{ old('expedido', $user->persona->expedido) == $option ? 'selected' : '' }}>
                                                    {{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Fila para Caducidad de Carnet --}}
                        <div class="row align-items-end">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Fecha de Caducidad del Carnet</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-calendar-alt text-lightblue"></i></span>
                                        </div>
                                        <input type="date"
                                            class="form-control" name="ci_fecha_caducidad" id="ci_fecha_caducidad"
                                            value="{{ old('ci_fecha_caducidad', $user->persona->ci_fecha_caducidad ? \Carbon\Carbon::parse($user->persona->ci_fecha_caducidad)->format('Y-m-d') : '') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox"><input class="custom-control-input"
                                            type="checkbox" id="ci_es_indefinido" name="ci_es_indefinido"
                                            {{ old('ci_es_indefinido', $user->persona->ci_es_indefinido) ? 'checked' : '' }} value="1"><label
                                            for="ci_es_indefinido" class="custom-control-label">Indefinido</label></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Teléfono</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-phone text-lightblue"></i></span>
                                </div>
                                <input type="text" class="form-control"
                                    name="telefono" value="{{ old('telefono', $user->persona->telefono) }}">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Fecha de Nacimiento</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-calendar-day text-lightblue"></i></span>
                                </div>
                                <input type="date" class="form-control"
                                    name="fecha_nacimiento"
                                    value="{{ old('fecha_nacimiento', $user->persona->fecha_nacimiento ? \Carbon\Carbon::parse($user->persona->fecha_nacimiento)->format('Y-m-d') : '') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Columna 2: Datos de Acceso y Rol --}}
                    <div class="col-md-6">
                        <h5>Datos de Acceso</h5>
                        <hr>
                        <div class="form-group">
                            <label>Email (*)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope text-lightblue"></i></span>
                                </div>
                                <input type="email" class="form-control"
                                    name="email" value="{{ old('email', $user->email) }}" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Nueva Contraseña (Opcional)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock text-lightblue"></i></span>
                                </div>
                                <input type="password" class="form-control" name="password">
                            </div>
                            <small class="form-text text-muted">Deje en blanco para no cambiar la contraseña.</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirmar Nueva Contraseña</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock text-lightblue"></i></span>
                                </div>
                                <input type="password" class="form-control" name="password_confirmation">
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="form-group">
                    <a href="{{ url('/home') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                </div>
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