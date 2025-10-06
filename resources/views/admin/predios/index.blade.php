@extends('adminlte::page')

@section('title', 'Gestión de Predios')

@section('plugins.Datatables', true) {{-- Activa el plugin de DataTables --}}

@section('content_header')
    <h1><b>Gestión de Predios</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Listado de Predios Registrados</h3>
            <div class="card-tools">
                <a href="{{ route('admin.predios.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Registrar Nuevo Predio
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="prediosTable" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Código Catastral</th>
                            <th>Propietario(s)</th>
                            <th>Manzano</th>
                            <th>Lote</th>
                            <th>Planimetría</th>
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
            var table = $('#prediosTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: "{{ route('admin.predios.index') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'codigo_catastral', name: 'codigo_catastral' },
                    { data: 'propietarios', name: 'propietarios.persona.nombre', orderable: false },
                    { data: 'manzano', name: 'manzano' },
                    { data: 'lote', name: 'lote' },
                    { data: 'planimetria', name: 'planimetria.codigo' },
                    { data: 'acciones', name: 'acciones', orderable: false, searchable: false }
                ],
                language: { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                 order: [[ 1, "desc" ]] // Ordena por código catastral por defecto
            });

            // Script para notificaciones "toast"
            @if(session('success'))
                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                Toast.fire({ icon: 'success', title: '{{ session('success') }}' });
            @endif

            // Delegación de eventos para el botón de desactivar
            $('#prediosTable').on('submit', '.form-delete', function(e) {
                e.preventDefault();
                var form = this;
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡El predio será desactivado!",
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
        });
    </script>
@stop