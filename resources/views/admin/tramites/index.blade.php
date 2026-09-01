@extends('adminlte::page')

@section('title', 'Gestión de Trámites')

@section('plugins.Datatables', true)

@section('content_header')
    <h1><b>Gestión de Trámites {{ $estadoFilter !== 'todos' ? '- ' . strtoupper($estadoFilter) : '' }}</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Lista de Registros</h3>
            <div class="card-tools ml-auto">
                <a href="{{ route('admin.tramites.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Iniciar Nuevo Trámite
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tramitesTable" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Hoja de Ruta</th>
                            <th>Tipo de Trámite</th>
                            <th>Cód. Catastral</th>
                            <th>Solicitante</th>
                            <th>Fecha Ingreso</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            var table = $('#tramitesTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    //url: "{{ route('admin.tramites.index') }}",
                    url: "/admin/tramites", // Ruta relativa para evitar problemas con subdirectorios
                    data: function(d) {
                        // Leer el parámetro 'estado' de la barra de direcciones del navegador
                        const urlParams = new URLSearchParams(window.location.search);
                        d.estado = urlParams.get('estado') || 'todos';
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'hoja_ruta', name: 'hoja_ruta' },
                    { data: 'tipo', name: 'tipo.nombre' },
                    { data: 'predio', name: 'predio.codigo_catastral' },
                    { data: 'solicitante', name: 'solicitante.nombre' },
                    { data: 'fecha_ingreso', name: 'fecha_ingreso' },
                    { data: 'estado', name: 'estado.nombre' },
                    { data: 'acciones', name: 'acciones', orderable: false, searchable: false }
                ],
                language: { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                order: [[5, "desc"]]
            });

            @if(session('success'))
                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                Toast.fire({ icon: 'success', title: '{{ session('success') }}' });
            @endif

            $('#tramitesTable').on('submit', 'form', function(e) {
                e.preventDefault();
                var form = this;
                var isRestore = $(form).hasClass('form-restore');
                var config = {
                    title: isRestore ? '¿Reactivar Trámite?' : '¿Archivar Trámite?',
                    text: isRestore ? "El trámite volverá a la lista de activos." : "¡El trámite será movido al archivo!",
                    icon: isRestore ? 'info' : 'warning',
                    confirmButtonText: isRestore ? 'Sí, ¡reactivar!' : 'Sí, ¡archivar!',
                    confirmButtonColor: isRestore ? '#28a745' : '#d33',
                };
                
                Swal.fire({
                    ...config,
                    showCancelButton: true,
                    cancelButtonColor: '#6c757d',
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