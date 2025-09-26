@extends('adminlte::page')

@section('title', 'Gestión de Propietarios')

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
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre Completo</th>
                            <th>Carnet</th>
                            <th>Municipio</th>
                            <th>Estado</th>
                            <th style="width: 150px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($propietarios as $propietario)
                            <tr class="{{ !$propietario->estado ? 'table-secondary text-muted' : '' }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $propietario->persona->nombre }} {{ $propietario->persona->primer_apellido }} {{ $propietario->persona->segundo_apellido }}</td>
                                <td>{{ $propietario->persona->carnet }} {{ $propietario->persona->expedido }}</td>
                                <td>{{ $propietario->municipio->nombre }}</td>
                                <td>
                                    @if($propietario->estado)
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    @if($propietario->estado)
                                        <a href="{{ route('admin.propietarios.edit', $propietario) }}" class="btn btn-sm btn-warning">Editar</a>
                                        <form action="{{ route('admin.propietarios.destroy', $propietario) }}" method="POST" class="d-inline form-delete">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Desactivar</button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.propietarios.restore', $propietario->id) }}" method="POST" class="d-inline form-restore">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-info">Reactivar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No hay propietarios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('js')
    {{-- Scripts de SweetAlert para confirmaciones --}}
    <script>
        $(document).ready(function() {
            @if(session('success'))
                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                Toast.fire({ type: 'success', title: '{{ session('success') }}' });
            @endif

            $('.form-delete').on('submit', function(e) {
                e.preventDefault();
                var form = this;
                Swal.fire({
                    title: '¿Estás seguro?', text: "¡El propietario será desactivado!", type: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, ¡desactivar!', cancelButtonText: 'Cancelar'
                }).then((result) => { if (result.value) { form.submit(); } });
            });

            $('.form-restore').on('submit', function(e) {
                e.preventDefault();
                var form = this;
                Swal.fire({
                    title: '¿Estás seguro?', text: "¡El propietario será reactivado!", type: 'info',
                    showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, ¡reactivar!', cancelButtonText: 'Cancelar'
                }).then((result) => { if (result.value) { form.submit(); } });
            });
        });
    </script>
@stop

