@extends('adminlte::page')

@section('title', 'Gestión de Propietarios')

@section('css')
@stop

@section('content_header')
    <h1><b>Gestión de Propietarios</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Propietarios Registrados</h3>
            <div class="card-tools">
                <a href="{{ route('admin.propietarios.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Registrar Nuevo Propietario
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="propietariosTable" class="table table-bordered table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre Completo</th>
                            <th>Carnet</th>
                            <th>Municipio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    {{-- El cuerpo de la tabla se llenará vía AJAX --}}
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // --- INICIALIZACIÓN DE DATATABLES CON SERVER-SIDE ---
            var table = $('#propietariosTable').DataTable({
                processing: true, // Muestra un indicador de "procesando"
                serverSide: true, // Activa el modo de procesamiento del lado del servidor
                responsive: true,
                autoWidth: false,
                ajax: "{{ route('admin.propietarios.index') }}", // La ruta que procesará los datos
                columns: [ // Define las columnas y su correspondencia con los datos del servidor
                    { data: 'id', name: 'id' },
                    { data: 'nombre_completo', name: 'nombre_completo' },
                    { data: 'carnet', name: 'carnet' },
                    { data: 'municipio', name: 'municipio' },
                    { data: 'estado', name: 'estado', orderable: false, searchable: false },
                    { data: 'acciones', name: 'acciones', orderable: false, searchable: false }
                ],
                language: { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
            });

            // --- CÓDIGO DE SWEETALERT (MODIFICADO PARA FUNCIONAR CON AJAX) ---
            @if(session('success'))
                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                Toast.fire({ icon: 'success', title: '{{ session('success') }}' });
            @endif

            // Necesitamos usar delegación de eventos porque los botones se crean dinámicamente
            $('#propietariosTable').on('submit', '.form-delete', function(e) {
                e.preventDefault();
                var form = this;
                Swal.fire({
                    title: '¿Estás seguro?', text: "¡El propietario será desactivado!", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, ¡desactivar!', cancelButtonText: 'Cancelar'
                }).then((result) => { if (result.value) { form.submit(); } });
            });

            $('#propietariosTable').on('submit', '.form-restore', function(e) {
                e.preventDefault();
                var form = this;
                Swal.fire({
                    title: '¿Estás seguro?', text: "¡El propietario será reactivado!", icon: 'info',
                    showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, ¡reactivar!', cancelButtonText: 'Cancelar'
                }).then((result) => { if (result.value) { form.submit(); } });
            });
        });
    </script>
@stop