@extends('adminlte::page')

@section('title', 'Configurar 2FA')

@section('content_header')
    <h1><b>Configurar Autenticación de Dos Factores (2FA)</b></h1>
@stop

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Estado de la Autenticación de Dos Factores</div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                        @endif

                        {{-- Si el usuario YA TIENE 2FA activado --}}
                        @if(Auth::user()->google2fa_secret)
                            <div class="alert alert-success">La autenticación de dos factores está actualmente <strong>activada</strong>.</div>
                            <p>Si desea desactivarla, ingrese su contraseña actual para confirmar.</p>
                            
                            <form action="{{ route('2fa.disable') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="password">Contraseña Actual</label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                    @error('password')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                                <button type="submit" class="btn btn-danger">Desactivar 2FA</button>
                            </form>

                        {{-- Si el usuario NO TIENE 2FA activado --}}
                        @else
                            <div class="alert alert-danger">La autenticación de dos factores está <strong>desactivada</strong>.</div>
                            <p>Para activarla, siga estos pasos:</p>
                            <ol>
                                <li>Escanee el siguiente código QR con su aplicación de autenticación.</li>
                                <li>Guarde la clave secreta en un lugar seguro.</li>
                                <li>Ingrese el código de 6 dígitos que genera su aplicación para verificar.</li>
                            </ol>

                            <div class="text-center">{!! $qrCodeInline !!}</div>
                            <div class="alert alert-warning mt-3"><strong>Clave Secreta:</strong> <code>{{ $secretKey }}</code></div>
                            
                            <hr>
                            <form action="{{ route('2fa.enable.post') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="one_time_password">Código de Verificación de 6 Dígitos</label>
                                    <input type="text" name="one_time_password" class="form-control @error('one_time_password') is-invalid @enderror" required>
                                    @error('one_time_password')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                                </div>
                                <button type="submit" class="btn btn-primary">Verificar y Activar</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop