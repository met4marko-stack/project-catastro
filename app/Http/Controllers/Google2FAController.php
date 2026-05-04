<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FALaravel\Google2FA;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class Google2FAController extends Controller
{
    public function showEnableForm(Request $request)
    {
        $user = Auth::user();
        $google2fa = app(Google2FA::class);

        // Generar y guardar la clave secreta en la sesión temporalmente
        $secretKey = $google2fa->generateSecretKey();
        $request->session()->put('2fa_temp_secret', $secretKey);

        $qrCodeInline = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secretKey 
        );

        return view('auth.2fa_enable', [
            'qrCodeInline' => $qrCodeInline,
            'secretKey' => $secretKey
        ]);
    }

    /**
     * Verifica y activa 2FA para el usuario.
     */
    public function enable2fa(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|digits:6'
        ]);

        $user = User::find(Auth::id());
        $google2fa = app(Google2FA::class);
        $secret = $request->session()->get('2fa_temp_secret');

        // Verificar que el código OTP sea válido
        $isValid = $google2fa->verifyKey($secret, $request->one_time_password);

        if ($isValid) {
            // Si es válido, guardar la clave en la base de datos
            $user->google2fa_secret = $secret;
            $user->save();

            // Limpiar la clave temporal de la sesión
            $request->session()->forget('2fa_temp_secret');

            return redirect()->route('2fa.enable')->with('success', '¡Autenticación de dos factores activada exitosamente!');
        }

        return back()->withErrors(['one_time_password' => 'El código ingresado es incorrecto. Inténtelo de nuevo.']);
    }

    /**
     * Deshabilita la autenticación de dos factores para el usuario.
     */
    public function disable2fa(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        $user = User::find(Auth::id());
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'La contraseña ingresada es incorrecta.']);
        }
        $user->google2fa_secret = null;
        $user->save();
        return redirect()->route('2fa.enable')->with('success', 'La autenticación de dos factores ha sido desactivada.');
    }

    /**
     * Muestra el formulario para ingresar el código 2FA durante el login.
     */
    public function showVerifyForm()
    {
        return view('auth.2fa_verify');
    }

    /**
     * Verifica el código 2FA ingresado por el usuario durante el login.
     */
    public function verifyCode(Request $request)
    {
        $request->validate(['one_time_password' => 'required|digits:6']);
        $user = User::findOrFail(session('2fa_user_id'));
        $google2fa = app(Google2FA::class);
        $isValid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);
        if ($isValid) {
            session()->forget('2fa_user_id');
            Auth::login($user);
            return redirect('/home');
        }
        return back()->withErrors(['one_time_password' => 'El código ingresado es incorrecto.']);
    }
}