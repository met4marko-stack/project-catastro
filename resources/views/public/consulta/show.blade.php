@extends('layouts.public')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4>Estado del Trámite: {{ $tramite->hoja_ruta }}</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Estado Actual:</strong> {{ $tramite->estado->nombre }}
                    </div>
                    <h5>Información General</h5>
                    <ul>
                        <li><strong>Tipo de Trámite:</strong> {{ $tramite->tipo->nombre }}</li>
                        <li><strong>Fecha de Ingreso:</strong> {{ $tramite->fecha_ingreso->format('d/m/Y') }}</li>
                    </ul>
                    <hr>
                    <h5>Observaciones</h5>
                    @if($tramite->observaciones)
                        <pre>{{ $tramite->observaciones }}</pre>
                    @else
                        <p>No hay observaciones generales para este trámite.</p>
                    @endif

                    <hr>
                    <h5>Requisitos Presentados</h5>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Documento / Requisito</th>
                                <th>Estado</th>
                                <th>Observación del Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tramite->documentos as $doc)
                                <tr>
                                    <td>{{ $doc->requisito->nombre }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $doc->estado->color_ui ?? '#6c757d' }}; color:white;">
                                            {{ $doc->estado->nombre }}
                                        </span>
                                    </td>
                                    <td>{{ $doc->observaciones ?? 'Sin observaciones' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="{{ route('public.consulta.index') }}" class="btn btn-secondary">Volver a consultar</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection