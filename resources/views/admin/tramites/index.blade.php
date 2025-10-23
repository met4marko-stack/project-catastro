@extends('adminlte::page')

@section('title', 'Gestión de Trámites')

@section('plugins.Datatables', true)

@section('content_header')
    <h1><b>Gestión de Trámites</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#" data-status="active">Activos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-status="inactive">Archivados</a>
                </li>
            </ul>
            <div class="card-tools">
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
                    url: "{{ route('admin.tramites.index') }}",
                    data: function(d) {
                        d.status = $('.nav-tabs .nav-link.active').data('status') || 'active';
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

            $('.nav-tabs a').on('click', function(e) {
                e.preventDefault();
                $('.nav-tabs .nav-link').removeClass('active');
                $(this).addClass('active');
                table.ajax.reload();
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