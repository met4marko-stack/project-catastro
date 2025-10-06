@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])

@php( $login_url = View::getSection('login_url') ?? config('adminlte.login_url', 'login') )
@php( $register_url = View::getSection('register_url') ?? config('adminlte.register_url', 'register') )
@php( $password_reset_url = View::getSection('password_reset_url') ?? config('adminlte.password_reset_url', 'password/reset') )

@if (config('adminlte.use_route_url', false))
    @php( $login_url = $login_url ? route($login_url) : '' )
    @php( $register_url = $register_url ? route($register_url) : '' )
    @php( $password_reset_url = $password_reset_url ? route($password_reset_url) : '' )
@else
    @php( $login_url = $login_url ? url($login_url) : '' )
    @php( $register_url = $register_url ? url($register_url) : '' )
    @php( $password_reset_url = $password_reset_url ? url($password_reset_url) : '' )
@endif

@section('auth_header', 'Verificación de Dos Factores')

@section('auth_body')
    <form action="{{ route('2fa.verify.post') }}" method="POST">
        @csrf

        <p class="login-box-msg">Por favor, ingrese el código de 6 dígitos de su aplicación de autenticación para continuar.</p>

        {{-- Campo para el Código --}}
        <div class="input-group mb-3">
            <input type="text" name="one_time_password" class="form-control @error('one_time_password') is-invalid @enderror"
                   placeholder="Código de 6 dígitos" autofocus required>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock {{ config('adminlte.classes_auth_icon', '') }}"></span>
                </div>
            </div>
            @error('one_time_password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Botón de Verificación --}}
        <button type="submit" class="btn btn-block {{ config('adminlte.classes_auth_btn', 'btn-flat btn-primary') }}">
            <span class="fas fa-check"></span>
            Verificar Código
        </button>
    </form>
@stop
