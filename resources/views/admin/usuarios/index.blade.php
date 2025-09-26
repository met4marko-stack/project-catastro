@extends('adminlte::page')

@section('title', 'Gestión de Usuarios')

@section('content_header')
    <h1><b>Gestión de Usuarios</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Usuarios Registrados</h3>
            <div class="card-tools">
                <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Nuevo Usuario
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Municipio</th>
                            <th>Roles</th>
                            <th>Estado</th>
                            <th style="width: 150px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Se filtra la colección para excluir al usuario logeado --}}
                        @forelse ($users->where('id', '!=', Auth::id()) as $user)
                            <tr class="{{ $user->trashed() ? 'table-secondary text-muted' : '' }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $user->persona->nombre ?? '' }} {{ $user->persona->primer_apellido ?? '' }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->municipio->nombre ?? 'N/A' }}</td>
                                <td>
                                    @foreach($user->roles as $role)
                                        <span class="badge badge-info">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @if($user->trashed())
                                        <span class="badge badge-danger">Inactivo</span>
                                    @else
                                        <span class="badge badge-success">Activo</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->trashed())
                                        {{-- Botón para Reactivar --}}
                                        <form action="{{ route('admin.usuarios.restore', $user->id) }}" method="POST" class="d-inline form-restore">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-info">Reactivar</button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.usuarios.edit', $user) }}" class="btn btn-sm btn-warning">Editar</a>
                                        <form action="{{ route('admin.usuarios.destroy', $user) }}" method="POST" class="d-inline form-delete">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Desactivar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No hay otros usuarios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Script para notificaciones "toast"
            @if(session('success'))
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
                Toast.fire({
                    type: 'success',
                    title: '{{ session('success') }}'
                });
            @endif

            // Script para la confirmación de DESACTIVACIÓN
            $('.form-delete').on('submit', function(e) {
                e.preventDefault();
                var form = this;
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡El usuario será desactivado!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, ¡desactivar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.value) {
                        form.submit();
                    }
                });
            });

            // Script para la confirmación de REACTIVACIÓN
            $('.form-restore').on('submit', function(e) {
                e.preventDefault();
                var form = this;
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡El usuario será reactivado en el sistema!",
                    type: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, ¡reactivar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.value) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@stop

