<?php

namespace App\Providers;

//use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    protected $policies = [
        //
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Permiso para ver secciones exclusivas del Super-Admin.
        // Devuelve 'true' si el usuario tiene el rol 'Super-Admin'.
        Gate::define('ver-seccion-super-admin', function (User $user) {
            return $user->hasRole('Super-Admin');
        });

        // Permiso para ver secciones generales de administración (Super-Admin y Admin-Municipal).
        // Devuelve 'true' si el usuario tiene cualquiera de los dos roles.
        Gate::define('ver-seccion-admin-general', function (User $user) {
            return $user->hasRole(['Super-Admin', 'Admin-Municipal']);
        });

        // Permiso para ver secciones exclusivas del Super-Admin.
        // Devuelve 'true' si el usuario tiene el rol 'Admin-Municipal.
        Gate::define('ver-seccion-admin-municipal', function (User $user) {
            return $user->hasRole('Admin-Municipal');
        });

        // Gate para gestionar trámites
        Gate::define('gestionar-tramites', function (User $user) {
            // Un Super-Admin siempre tiene acceso
            if ($user->hasRole('Super-Admin')) {
                return true;
            }
            // Permitir a los roles específicos
            return $user->hasRole(['Admin-Municipal', 'Asesor-Legal', 'Inspector-Tecnico']);
        });
    }
}
