@extends('adminlte::page')

@section('title', 'Editar Propietario')

@section('content_header')
    <h1><b>Editar Propietario</b></h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Modifique los datos del formulario</h3></div>
        <div class="card-body">
             @if ($errors->any())
                <div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form action="{{ route('admin.propietarios.update', $propietario) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.propietarios.partials.form-fields')
                <hr>
                <div class="form-group">
                    <a href="{{ route('admin.propietarios.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Propietario</button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
    function handleCiExpiration() {
        var checkbox = document.getElementById('ci_es_indefinido');
        var fechaCaducidadInput = document.getElementById('ci_fecha_caducidad');
        if (checkbox.checked) {
            fechaCaducidadInput.disabled = true;
            fechaCaducidadInput.value = '';
        } else {
            fechaCaducidadInput.disabled = false;
        }
    }
    document.addEventListener('DOMContentLoaded', handleCiExpiration);
    document.getElementById('ci_es_indefinido').addEventListener('change', handleCiExpiration);
</script>
@stop

