@extends('adminlte::page')

@section('title', 'Generar Certificación Varia')

@section('content_header')
    <h1>Generar Certificación Técnica Varia</h1>
@stop

@section('content')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Completar datos para la Certificación</h3>
        </div>
        <form action="{{ route('admin.tramites.generateCertificacionVaria', $tramite) }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="alert alert-info">
                    Está a punto de generar una certificación para el trámite <strong>{{ $tramite->hoja_ruta }}</strong>
                    del solicitante <strong>{{ $tramite->solicitante->nombre_completo }}</strong>.
                    Por favor, complete los campos que se imprimirán en el documento.
                </div>

                <div class="form-group">
                    <label for="titulo_certificado">Título Principal del Certificado (*)</label>
                    <input type="text" name="titulo_certificado" class="form-control" 
                           placeholder="Ej: CERTIFICACION DE AREA URBANA" 
                           value="{{ old('titulo_certificado', 'CERTIFICACION DE AREA URBANA') }}" required>
                </div>



                <div class="form-group">
                    <label for="parrafo_dos_negrita">Párrafo 2 (Texto en negrita del 3er párrafo) (*)</label>
                    <textarea name="parrafo_dos_negrita" class="form-control" rows="3" required 
                              placeholder="Ej: CERTIFICACION DE AREA URBANA">{{ old('parrafo_dos_negrita', 'CERTIFICACION DE AREA URBANA') }}</textarea>
                </div>

            </div>
            <div class="card-footer">
                <a href="{{ route('admin.tramites.show', $tramite) }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Generar PDF</button>
            </div>
        </form>
    </div>
@stop