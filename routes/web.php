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
use App\Http\Controllers\PublicConsultaController;
use App\Http\Controllers\TramiteController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::get('/', function () {
    return view('welcome');
});

// --- RUTAS DE CONSULTA PÚBLICA ---
Route::get('/consulta', [PublicConsultaController::class, 'index'])->name('public.consulta.index');
Route::post('/consulta/buscar', [PublicConsultaController::class, 'buscar'])->name('public.consulta.buscar');

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
        ->middleware('role:Super-Admin|Admin-Municipal|Asesor-Legal|Inspector-Tecnico');

    // --- Rutas del Panel de Administración ---
    Route::prefix('admin')->name('admin.')->group(function () {

        Route::get('perfil', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('perfil', [ProfileController::class, 'update'])->name('profile.update');

        // Módulo de Municipios: accesible SOLO para Super-Admin
        Route::resource('municipios', MunicipioController::class)
            ->middleware('role:Super-Admin');

        // Ruta para Reactivar un usuario (Soft Delete)
        Route::post('usuarios/{usuario}/restore', [UsuarioController::class, 'restore'])
            ->name('usuarios.restore')
            ->middleware('role:Super-Admin|Admin-Municipal');
        Route::resource('usuarios', UsuarioController::class)
            ->middleware('role:Super-Admin|Admin-Municipal');

        // Módulo de Historial de Asignaciones: accesible SOLO para Super-Admin
        Route::get('asignaciones', [AsignacionController::class, 'index'])
            ->name('asignaciones.index')
            ->middleware('role:Super-Admin');

        // Módulo de Propietarios
        // Ruta para Reactivar un propietario
        Route::post('propietarios/{id}/restore', [PropietarioController::class, 'restore'])
            ->name('propietarios.restore')
            ->middleware('role:Super-Admin|Admin-Municipal');
        // --- RUTA AÑADIDA PARA EL OCR ---
        Route::post('propietarios/procesar-ocr', [PropietarioController::class, 'procesarOcr'])
            ->name('propietarios.procesarOcr')
            ->middleware('role:Super-Admin|Admin-Municipal');
        // Módulo de Propietarios
        Route::resource('propietarios', PropietarioController::class)
            ->middleware('role:Admin-Municipal');

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
        Route::get('predios/{predio}/get-propietarios', [App\Http\Controllers\PredioController::class, 'getPropietariosAjax'])->name('predios.getPropietariosAjax');
        Route::resource('predios', App\Http\Controllers\PredioController::class)
            ->middleware('role:Admin-Municipal');

        // --- RUTA PARA TRÁMITES ---
        Route::middleware('can:gestionar-tramites')->group(function () {
            // --- RUTAS PARA BÚSQUEDA DINÁMICA DE SOLICITANTES ---
            Route::get('personas/search-ajax', [App\Http\Controllers\PersonaController::class, 'searchAjax'])->name('personas.searchAjax');
            // --- RUTA PARA REGISTRO RÁPIDO DE PERSONAS (APODERADOS) ---
            Route::post('personas/store-ajax', [App\Http\Controllers\PersonaController::class, 'storeAjax'])->name('personas.storeAjax');
            // Ruta para añadir un documento a un trámite existente
            Route::post('tramites/{tramite}/add-documento', [TramiteController::class, 'addDocumento'])->name('tramites.addDocumento');
            // --- RUTA PARA VER/DESCARGAR UN DOCUMENTO ---
            Route::get('tramites/documento/{documentoId}', [TramiteController::class, 'verDocumento'])->name('tramites.verDocumento');
            // Ruta para actualizar el estado o fechas de un trámite
            Route::put('tramites/{tramite}/update-status', [TramiteController::class, 'updateStatus'])->name('tramites.updateStatus');
            // --- RUTA PARA ACTUALIZAR EL ESTADO DE UN DOCUMENTO ESPECÍFICO ---
            Route::post('tramites/{tramite}/documentos/{documento}/update-status', [TramiteController::class, 'updateDocumentoStatus'])->name('tramites.documento.updateStatus');
            // --- RUTA PARA GENERAR LA CERTIFICACIÓN TÉCNICA ---
            Route::get('tramites/{tramite}/generar-certificacion', [TramiteController::class, 'generarCertificacionTecnica'])->name('tramites.generarCertificacion');
            // Ruta para MOSTRAR el formulario de datos manuales
            Route::get('tramites/{tramite}/certificacion-varia-form', [TramiteController::class, 'showCertificacionVariaForm'])->name('tramites.certificacionVariaForm');
            // Ruta para PROCESAR el formulario y generar el PDF final
            Route::post('tramites/{tramite}/generar-certificacion-varia', [TramiteController::class, 'generateCertificacionVaria'])->name('tramites.generateCertificacionVaria');

            Route::post('tramites/{tramite}/restore', [TramiteController::class, 'restore'])->name('tramites.restore');
            Route::resource('tramites', TramiteController::class);
        });

        // Módulo de Planimetrías
        Route::get('planimetrias/visualizacion', [PlanimetriaController::class, 'visualizacion'])
            ->name('planimetrias.visualizacion')
            ->middleware('role:Admin-Municipal');
        Route::resource('planimetrias', PlanimetriaController::class)
            ->middleware('role:Admin-Municipal');
    });
});
