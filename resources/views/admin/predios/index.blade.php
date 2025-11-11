@extends('adminlte::page')

@section('title', 'Gestión de Predios')

@section('plugins.Datatables', true) {{-- Activa el plugin de DataTables --}}

@section('content_header')
    <h1><b>Gestión de Predios</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            {{-- PESTAÑAS AÑADIDAS --}}
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#" data-status="active">Activos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-status="inactive">Desactivados</a>
                </li>
            </ul>
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
                
                // --- AJAX MODIFICADO ---
                ajax: {
                    url: "{{ route('admin.predios.index') }}",
                    data: function(d) {
                        d.status = $('.nav-tabs .nav-link.active').data('status') || 'active';
                    }
                },
                // --- FIN DE AJAX MODIFICADO ---

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
                order: [[ 1, "desc" ]] 
            });

            // --- SCRIPT DE PESTAÑAS AÑADIDO ---
            $('.nav-tabs a').on('click', function(e) {
                e.preventDefault();
                $('.nav-tabs .nav-link').removeClass('active');
                $(this).addClass('active');
                table.ajax.reload();
            });
            // --- FIN DE SCRIPT DE PESTAÑAS ---

            // Script para notificaciones "toast"
            @if(session('success'))
                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                Toast.fire({ icon: 'success', title: '{{ session('success') }}' });
            @endif

            // --- LÓGICA DE SWAL ACTUALIZADA ---
            $('#prediosTable').on('submit', 'form', function(e) {
                e.preventDefault();
                var form = this;
                var isRestore = $(form).hasClass('form-restore');
                
                var config = {
                    title: isRestore ? '¿Reactivar Predio?' : '¿Desactivar Predio?',
                    text: isRestore ? "El predio volverá a la lista de activos." : "¡El predio será desactivado!",
                    icon: isRestore ? 'info' : 'warning',
                    confirmButtonText: isRestore ? 'Sí, ¡reactivar!' : 'Sí, ¡desactivar!',
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