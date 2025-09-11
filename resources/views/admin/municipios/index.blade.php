@extends('adminlte::page')

@section('title', 'Gestión de Municipios')

@section('content_header')
    <h1><b>Gestión de Municipios</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Municipios Registrados</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createModal">
                    <i class="fas fa-plus"></i> Crear Nuevo Municipio
                </button>
            </div>
        </div>
        <div class="card-body">
            {{-- Se envuelve la tabla en un div con la clase table-responsive --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Logo</th>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th style="width: 200px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($municipios as $municipio)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <img src="{{ $municipio->logo ? asset('storage/' . $municipio->logo) : 'https://placehold.co/100x50/e9ecef/495057?text=Sin+Logo' }}"
                                         alt="Logo" class="img-thumbnail" width="100">
                                </td>
                                <td>{{ $municipio->nombre }}</td>
                                <td>{{ $municipio->departamento }}</td>
                                <td>
                                    <!-- Botón de Editar -->
                                    <button type="button" class="btn btn-sm btn-warning btn-action" data-toggle="modal" data-target="#editModal{{ $municipio->id }}">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    
                                    <!-- Formulario de Eliminación -->
                                    <form action="{{ route('admin.municipios.destroy', $municipio) }}" method="POST" class="d-inline form-delete">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger btn-action">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Modal de Edición para cada municipio -->
                            @include('admin.municipios.partials.edit-modal')

                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No hay municipios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Creación -->
    @include('admin.municipios.partials.create-modal')
@stop

@section('css')
    {{-- Estilos para igualar el tamaño de los botones de acción --}}
    <style>
        .btn-action {
            width: 90px;
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Script para mostrar notificaciones "toast"
            @if(session('success'))
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
                Toast.fire({
                    type: 'success', 
                    title: '{{ session('success') }}'
                });
            @endif

            // Script para la confirmación de eliminación con SweetAlert2
            $('.form-delete').on('submit', function(e) {
                e.preventDefault();
                var form = this;
                
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡Esta acción no se puede deshacer!",
                    type: 'warning', 
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, ¡eliminar!',
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

