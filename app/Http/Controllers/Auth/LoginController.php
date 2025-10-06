<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Este método se ejecuta después de que un usuario se autentica correctamente.
     * Aquí es donde interceptamos el flujo para el 2FA.
     */
    protected function authenticated(Request $request, $user)
    {
        // Si el usuario tiene 2FA activado
        if ($user->google2fa_secret) {
            // Guardamos el ID del usuario en sesión y cerramos la sesión principal
            session(['2fa_user_id' => $user->id]);
            Auth::logout();

            // Redirigimos a la página de verificación 2FA
            return redirect()->route('2fa.verify');
        }

        // Si no tiene 2FA, el flujo continúa normalmente
        return redirect()->intended($this->redirectPath());
    }
}
