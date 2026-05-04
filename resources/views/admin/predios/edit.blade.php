@extends('adminlte::page')
@section('title', 'Editar Predio')
@section('content_header')
    <h1>Editar Predio</h1>
@stop
@section('content')
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-ban"></i> ¡Error!</h5>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.predios.update', $predio->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.predios.partials._form-fields')
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
@stop
@section('plugins.Select2', true)
@section('js')
<script>
$(document).ready(function() {
    $('.select2').select2();
    // Llenar los selects con los datos del $predio
    $('#planimetria_id').val('{{ $predio->planimetria_id }}').trigger('change');
    $('#propietarios').val(@json($predio->propietarios->pluck('id'))).trigger('change');
    $('select[name="provincia_id"]').val('{{ $predio->provincia_id }}').trigger('change');
    $('select[name="centro_poblado_id"]').val('{{ $predio->centro_poblado_id }}').trigger('change');

    // Función para actualizar el código catastral (Misma lógica que en create)
    function updateCodigoCatastral() {
        let manzano = $('input[name="manzano"]').val();
        let lote = $('input[name="lote"]').val();
        const distrito = '01'; 

        let formattedManzano = '';
        if (manzano && !isNaN(parseInt(manzano))) {
            let mInt = parseInt(manzano);
            if (mInt >= 1 && mInt <= 9) {
                formattedManzano = '0' + mInt;
            } else {
                formattedManzano = String(mInt);
            }
        } else if (manzano) {
            formattedManzano = manzano;
        }

        let formattedLote = '';
        if (lote && !isNaN(parseInt(lote))) {
            let lInt = parseInt(lote);
            if (lInt >= 1 && lInt <= 9) {
                formattedLote = '0' + lInt;
            } else {
                formattedLote = String(lInt);
            }
        } else if (lote) {
            formattedLote = lote;
        }
        
        if (formattedManzano && formattedLote) {
            let codigoCatastral = distrito + formattedManzano + formattedLote;
            $('input[name="codigo_catastral"]').val(codigoCatastral);
        }
        // En edit no limpiamos si está vacío para no borrar el código existente por error al cargar
    }

    // Escuchar cambios
    $('input[name="manzano"]').on('blur', updateCodigoCatastral);
    $('input[name="lote"]').on('blur', updateCodigoCatastral);
});
</script>
@stop