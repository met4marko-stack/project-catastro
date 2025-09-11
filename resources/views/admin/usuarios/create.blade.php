@extends('adminlte::page')

@section('title', 'Crear Nuevo Usuario')

@section('content_header')
    <h1><b>Creación de un Nuevo Usuario</b></h1>
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
                        <div class="form-group">
                            <label>Nombre(s) (*)</label>
                            <input type="text" class="form-control" name="nombre" value="{{ old('nombre') }}" required>
                            @error('nombre') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group">
                            <label>Primer Apellido (*)</label>
                            <input type="text" class="form-control" name="primer_apellido" value="{{ old('primer_apellido') }}" required>
                            @error('primer_apellido') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group">
                            <label>Segundo Apellido</label>
                            <input type="text" class="form-control" name="segundo_apellido" value="{{ old('segundo_apellido') }}">
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Carnet de Identidad (*)</label>
                                    <input type="text" class="form-control" name="carnet" value="{{ old('carnet') }}" required>
                                    @error('carnet') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Expedido</label>
                                    <select name="expedido" class="form-control">
                                        <option value="">--</option>
                                        @foreach($expedidoOptions as $option)
                                            <option value="{{ $option }}" {{ old('expedido') == $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        {{-- NUEVOS CAMPOS AÑADIDOS --}}
                        <div class="form-group">
                            <label>Teléfono / Celular</label>
                            <input type="text" class="form-control" name="telefono" value="{{ old('telefono') }}">
                        </div>
                        <div class="form-group">
                            <label>Fecha de Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}">
                        </div>
                    </div>

                    {{-- Columna 2: Datos de Acceso y Rol --}}
                    <div class="col-md-6">
                        <h5>Datos de Acceso y Rol</h5>
                        <hr>
                        <div class="form-group">
                            <label>Email (*)</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                            @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group">
                            <label>Contraseña (*)</label>
                            <input type="password" class="form-control" name="password" required>
                            @error('password') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-group">
                            <label>Confirmar Contraseña (*)</label>
                            <input type="password" class="form-control" name="password_confirmation" required>
                        </div>
                        <div class="form-group">
                            <label>Rol del Usuario (*)</label>
                            <select name="rol_id" class="form-control" required>
                                <option value="">-- Seleccione un Rol --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('rol_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('rol_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        {{-- Lógica condicional: Solo el Super-Admin ve este campo --}}
                        @if(Auth::user()->hasRole('Super-Admin'))
                            <div class="form-group">
                                <label>Municipio (*)</label>
                                <select name="municipio_id" class="form-control" required>
                                    <option value="">-- Seleccione un Municipio --</option>
                                    @foreach($municipios as $municipio)
                                        <option value="{{ $municipio->id }}" {{ old('municipio_id') == $municipio->id ? 'selected' : '' }}>{{ $municipio->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('municipio_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        @endif
                    </div>
                </div>

                <hr>
                <div class="form-group">
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
@stop
