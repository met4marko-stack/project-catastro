@extends('adminlte::page')

@section('title', 'Gestión de Usuarios')

@section('plugins.Datatables', true) {{-- Activa el plugin de DataTables --}}

@section('content_header')
    <h1><b>Gestión de Usuarios</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            {{-- INICIO: Pestañas de Filtro --}}
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#" data-status="active">Activos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-status="inactive">Inactivos</a>
                </li>
            </ul>
            {{-- FIN: Pestañas de Filtro --}}
            <div class="card-tools">
                <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Nuevo Usuario
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Municipio</th>
                            <th>Roles</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- El cuerpo se llenará vía AJAX --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Inicialización de DataTables
            var table = $('#usersTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route('admin.usuarios.index') }}",
                    // Añade el parámetro 'status' a la petición AJAX
                    data: function(d) {
                        d.status = $('.nav-tabs .nav-link.active').data('status') || 'active';
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nombre_completo',
                        name: 'persona.nombre'
                    }, // Permite ordenar por nombre
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'municipio',
                        name: 'municipio.nombre'
                    },
                    {
                        data: 'roles',
                        name: 'roles',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'estado',
                        name: 'estado',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'acciones',
                        name: 'acciones',
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                order: [
                    [0, "desc"]
                ] // Ordena por ID descendente por defecto
            });

            // Evento para cambiar de pestaña y recargar la tabla
            $('.nav-tabs a').on('click', function(e) {
                e.preventDefault();
                $('.nav-tabs .nav-link').removeClass('active');
                $(this).addClass('active');
                table.ajax.reload();
            });

            // Script para notificaciones "toast"
            @if (session('success'))
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: 'success',
                    title: '{{ session('success') }}'
                });
            @endif

            // Delegación de eventos para los botones de eliminar y restaurar
            $('#usersTable').on('submit', '.form-delete', function(e) {
                e.preventDefault();
                var form = this;
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡El usuario será desactivado!",
                    icon: 'warning',
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

            $('#usersTable').on('submit', '.form-restore', function(e) {
                e.preventDefault();
                var form = this;
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡El usuario será reactivado!",
                    icon: 'info',
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
