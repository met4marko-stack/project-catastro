@extends('adminlte::page')

@section('title', 'Generar Certificado de Línea y Nivel')

@section('content_header')
    <h1>Generar Certificado de Línea y Nivel (Trámite #{{ $tramite->id }})</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Completar datos para la certificación</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.tramites.generateLineaNivel', $tramite) }}" method="POST">
                @csrf

                <div class="alert alert-info">
                    <p><strong>Solicitante:</strong> {{ $tramite->solicitante->nombre_completo ?? 'N/A' }}</p>
                    <p><strong>Predio (Código Catastral):</strong> {{ $tramite->predio->codigo_catastral ?? 'N/A' }}</p>
                </div>

                <div class="form-group">
                    <label for="parrafo_dos">Párrafo de Acreditación (Párrafo 2)</label>
                    <p class="text-muted small">
                        Modifique el texto para que refleje los documentos presentados por el solicitante.
                    </p>
                    <textarea class="form-control" id="parrafo_dos" name="parrafo_dos" rows="6">{{ old('parrafo_dos', $defaultParrafoDos ?? '') }}</textarea>
                    @error('parrafo_dos')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-3">
                    <a href="{{ route('admin.tramites.show', $tramite) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> Generar PDF
                    </button>
                </div>

            </form>
        </div>
    </div>
@stop