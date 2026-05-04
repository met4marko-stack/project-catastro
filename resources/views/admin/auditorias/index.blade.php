@extends('adminlte::page')

@section('title', 'Registro de Auditoría')

@section('content_header')
    <h1><i class="fas fa-history"></i> Registro de Cambios del Sistema</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Historial de Operaciones</h3>
        </div>
        <div class="card-body">
            <table id="auditorias-table" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Evento</th>
                        <th>Modelo Afectado</th>
                        <th>Resumen Cambios</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            $('#auditorias-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.auditorias.index') }}",
                columns: [
                    { data: 'created_at', name: 'created_at' },
                    { data: 'user_id', name: 'user_id' }, // Corregido: usar user_id para usar el filtro personalizado
                    { data: 'event', name: 'event' },
                    { data: 'auditable_type', name: 'auditable_type' },
                    { data: 'resumen_cambios', name: 'new_values', orderable: false, searchable: false },
                    { data: 'acciones', name: 'acciones', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']], // Ordenar por fecha descendente
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });
        });
    </script>
@stop
