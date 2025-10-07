<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\PropietarioController;
use App\Http\Controllers\Google2FAController;
use App\Http\Controllers\OcrAIController;
use App\Http\Controllers\PlanimetriaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::get('/', function () {
    return view('welcome');
});

Route::post('/ocr/procesar', [OcrAIController::class, 'procesarDocumento'])->name('ocr.procesar');


Auth::routes();

// --- RUTAS PARA LA VERIFICACIÓN 2FA ---
Route::middleware('auth')->group(function () {
    Route::get('/2fa/enable', [Google2FAController::class, 'showEnableForm'])->name('2fa.enable');
    Route::post('/2fa/enable', [Google2FAController::class, 'enable2fa'])->name('2fa.enable.post');
    Route::post('/2fa/disable', [Google2FAController::class, 'disable2fa'])->name('2fa.disable');
});
Route::get('/2fa/verify', [App\Http\Controllers\Google2FAController::class, 'showVerifyForm'])->name('2fa.verify');
Route::post('/2fa/verify', [App\Http\Controllers\Google2FAController::class, 'verifyCode'])->name('2fa.verify.post');


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
        /*Route::get('test-phpinfo', function () {
            phpinfo();
        })->name('test.phpinfo');*/

        // --- Módulo de Predios ---
        Route::post('predios/procesar-plano', [App\Http\Controllers\PredioController::class, 'procesarPlano'])
            ->name('predios.procesarPlano')
            ->middleware('role:Admin-Municipal');
        Route::get('predios/buscar', [App\Http\Controllers\PredioController::class, 'buscar'])
            ->name('predios.buscar')
            ->middleware('role:Admin-Municipal');
        Route::resource('predios', App\Http\Controllers\PredioController::class)
            ->middleware('role:Admin-Municipal');
            
        Route::get('planimetrias/visualizacion', [PlanimetriaController::class, 'visualizacion'])
            ->name('planimetrias.visualizacion')
            ->middleware('role:Admin-Municipal');
        // La ruta del recurso ahora va después.
        Route::resource('planimetrias', PlanimetriaController::class)
            ->middleware('role:Admin-Municipal');
        
    });
});
