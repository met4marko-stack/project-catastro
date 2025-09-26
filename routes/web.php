<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\PropietarioController;

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
        Route::resource('municipios', MunicipioController::class)
            ->middleware('role:Super-Admin');
        
        // Módulo de Usuarios: accesible para Super-Admin y Admin-Municipal
        Route::resource('usuarios', UsuarioController::class)
            ->middleware('role:Super-Admin|Admin-Municipal');

        // Ruta para Reactivar un usuario (Soft Delete)
        Route::post('usuarios/{usuario}/restore', [UsuarioController::class, 'restore'])
            ->name('usuarios.restore')
            ->middleware('role:Super-Admin');

        // Módulo de Historial de Asignaciones: accesible SOLO para Super-Admin
        Route::get('asignaciones', [AsignacionController::class, 'index'])
            ->name('asignaciones.index')
            ->middleware('role:Super-Admin');

        // Módulo de Propietarios
        Route::resource('propietarios', PropietarioController::class)
             ->middleware('role:Admin-Municipal');

        // Ruta para Reactivar un propietario
        Route::post('propietarios/{id}/restore', [PropietarioController::class, 'restore'])
             ->name('propietarios.restore')
             ->middleware('role:Super-Admin|Admin-Municipal');
        
        // --- RUTA AÑADIDA PARA EL OCR ---
        Route::post('propietarios/procesar-ocr', [PropietarioController::class, 'procesarOcr'])
             ->name('propietarios.procesarOcr')
             ->middleware('role:Super-Admin|Admin-Municipal');

        // --- RUTA DE PRUEBA PARA VERIFICAR IMAGICK ---
        Route::get('test-phpinfo', function () {
            phpinfo();
        })->name('test.phpinfo');

        
    });
});

