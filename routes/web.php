<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\UsuarioController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::get('/', function () {
    return view('welcome');
});

Auth::routes();


// --- Rutas para usuarios autenticados ---
Route::middleware(['auth', 'nocache'])->group(function () {

    // Ruta Home: accesible para Super-Admin y Admin-Municipal
    Route::get('/home', [HomeController::class, 'index'])
         ->name('home')
         ->middleware('role:Super-Admin|Admin-Municipal');

    // --- Rutas del Panel de Administración ---
    Route::prefix('admin')->name('admin.')->group(function () {

        // Módulo de Municipios: accesible SOLO para Super-Admin
        // Usamos Route::resource para crear todas las rutas del CRUD (index, store, update, destroy, etc.)
        Route::resource('municipios', MunicipioController::class)
             ->middleware('role:Super-Admin');

        Route::resource('usuarios', UsuarioController::class)
             ->middleware('role:Super-Admin|Admin-Municipal');

    });
});

