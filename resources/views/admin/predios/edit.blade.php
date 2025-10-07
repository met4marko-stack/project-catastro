@extends('adminlte::page')
@section('title', 'Editar Predio')
@section('content_header')
    <h1>Editar Predio</h1>
@stop
@section('content')
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
});
</script>
@stop