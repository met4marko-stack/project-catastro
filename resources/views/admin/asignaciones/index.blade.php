@extends('adminlte::page')

@section('title', 'Historial de Asignaciones')

@section('content_header')
    <h1><b>Historial de Asignaciones de Usuarios</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Bitácora de cambios de municipio</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Usuario</th>
                            <th>Municipio Asignado</th>
                            <th>Fecha de Asignación</th>
                            <th>Fecha de Cese</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($asignaciones as $asignacion)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $asignacion->user->persona->nombre ?? '' }} {{ $asignacion->user->persona->primer_apellido ?? '' }}</td>
                                <td>{{ $asignacion->municipio->nombre ?? 'N/A' }}</td>
                                <td>{{ $asignacion->fecha_asignacion }}</td>
                                <td>{{ $asignacion->fecha_cese ?? 'N/A' }}</td>
                                <td>
                                    @if($asignacion->estado == 'Activo')
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-secondary">Inactivo</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No hay registros en el historial.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop