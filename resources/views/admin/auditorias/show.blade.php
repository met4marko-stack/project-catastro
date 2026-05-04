@extends('adminlte::page')

@section('title', 'Detalle de Auditoría')

@section('content_header')
    <h1><i class="fas fa-search"></i> Detalle del Cambio #{{ $audit->id }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">Metadatos</h3>
                </div>
                <div class="card-body">
                    <strong>Usuario Responsable</strong>
                    <p class="text-muted">{{ $audit->user ? $audit->user->name : 'Sistema' }}</p>
                    <hr>
                    <strong>Evento</strong>
                    <p class="text-muted">{{ ucfirst($audit->event) }}</p>
                    <hr>
                    <strong>Fecha y Hora</strong>
                    <p class="text-muted">{{ $audit->created_at->format('d/m/Y H:i:s') }}</p>
                    <hr>
                    <strong>Dirección IP</strong>
                    <p class="text-muted">{{ $audit->ip_address }}</p>
                    <hr>
                    <strong>Objeto Afectado</strong>
                    <p class="text-muted">{{ class_basename($audit->auditable_type) }} #{{ $audit->auditable_id }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Datos Modificados</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th class="text-danger">Valor Anterior</th>
                                <th class="text-success">Valor Nuevo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Combinar claves de old y new para asegurar que mostramos todo
                                $keys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
                            @endphp

                            @foreach($keys as $key)
                                @php
                                    $old = $oldValues[$key] ?? null;
                                    $new = $newValues[$key] ?? null;
                                    // Resaltar si cambió
                                    $changed = $old != $new;
                                @endphp
                                <tr style="{{ $changed ? 'background-color: #f9f9f9;' : '' }}">
                                    <td><strong>{{ $key }}</strong></td>
                                    <td class="text-danger">
                                        {{ is_array($old) ? json_encode($old) : ($old ?? '-') }}
                                    </td>
                                    <td class="text-success">
                                        {{ is_array($new) ? json_encode($new) : ($new ?? '-') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('admin.auditorias.index') }}" class="btn btn-secondary">Volver al Listado</a>
                </div>
            </div>
        </div>
    </div>
@stop
