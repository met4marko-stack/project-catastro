@extends('layouts.public')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Consultar Estado de Trámite</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('public.consulta.buscar') }}">
                        @csrf

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                {{ $errors->first('credenciales') }}
                            </div>
                        @endif

                        <div class="form-group row">
                            <label for="hoja_ruta" class="col-md-4 col-form-label text-md-right">N° de Hoja de Ruta</label>
                            <div class="col-md-6">
                                <input id="hoja_ruta" type="text" class="form-control" name="hoja_ruta" value="{{ old('hoja_ruta') }}" required autofocus>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="codigo_acceso" class="col-md-4 col-form-label text-md-right">Código de Acceso</label>
                            <div class="col-md-6">
                                <input id="codigo_acceso" type="text" class="form-control" name="codigo_acceso" required>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    Consultar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection