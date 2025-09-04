@extends('adminlte::page')

@section('title', 'Sistema de Administracion')

@section('content_header')
    <h1>Administración Central</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box zoomP">
                <img src="{{ url('/img/municipio.gif') }}" width="70px" alt="">
                <div class="info-box-content">
                    <span class="info-box-text">Municipios registrados</span>
                    <span class="info-box-number">0 municipios</span>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop
