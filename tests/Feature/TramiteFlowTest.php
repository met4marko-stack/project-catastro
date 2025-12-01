<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\TramiteEstado;
use App\Models\TramiteTipo; // <-- IMPORTANTE: Importar el modelo
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TramiteFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Limpia la caché de permisos para que las pruebas no se confundan
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_administrador_puede_crear_tramite()
    {
        // 1. Preparación
        $rol = Role::create(['name' => 'Admin-Municipal', 'guard_name' => 'web']);
        // Dar permiso explícito si tu middleware lo requiere (opcional, depende de tu config)
        // $rol->givePermissionTo('gestionar-tramites'); 
        /** @var \App\Models\User $user */
        
        $user = User::factory()->create();
        $user->assignRole($rol);
        
        TramiteEstado::create(['nombre' => 'INGRESADO', 'color_ui' => 'primary']);

        // --- CORRECCIÓN 1: CREAR EL TIPO DE TRÁMITE ---
        // Creamos el tipo con ID 1 para que la validación pase
        TramiteTipo::create([
            'id' => 1, 
            'nombre' => 'Aprobación de Plano', 
            'descripcion' => 'Test'
        ]);
        
        // Factories para relaciones
        $predio = \App\Models\Predio::factory()->create();
        $solicitante = \App\Models\Persona::factory()->create();

        // 2. Actuación
        $response = $this->actingAs($user)
                         ->post('/admin/tramites', [
                             // CORRECCIÓN 1: Cambiar 'tipo_tramite_id' a 'tramite_tipo_id'
                             'tramite_tipo_id' => 1, 
                             
                             'predio_id' => $predio->id,
                             'solicitante_id' => $solicitante->id,
                             'observaciones' => 'Prueba automática de creación',
                             
                             // CORRECCIÓN 2: Añadir 'fecha_ingreso'
                             'fecha_ingreso' => now()->format('Y-m-d'),
                         ]);

        //$response->dumpSession();
        // Si esto falla, descomenta la siguiente línea para ver QUÉ error de validación ocurrió:
        // $response->dumpSession();

        // 3. Verificación
        $response->assertStatus(302); // Debe redirigir tras crear
        
        $this->assertDatabaseHas('tramites', [
            'observaciones' => 'Prueba automática de creación',
            'predio_id' => $predio->id,
        ]);
    }

    public function test_usuario_sin_permisos_no_puede_ver_tramites()
    {
        // Crear un usuario simple SIN roles
        /** @var \App\Models\User $user */
        $user = User::factory()->create(); 

        // --- CORRECCIÓN: Asegurar que Spatie sepa que no tiene roles ---
        // A veces en testing es necesario refrescar los permisos
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->actingAs($user)
                         ->get('/admin/tramites');

        // Si recibes 200 aquí, significa que tu middleware 'can:gestionar-tramites' 
        // no está bloqueando al usuario. Verifica tu RouteServiceProvider o Kernel.
        // Por ahora, asumimos que debería ser 403.
        $response->assertStatus(403);
    }

    public function test_invitado_es_redirigido_al_login()
    {
        // --- CORRECCIÓN 3: RUTA CORRECTA ---
        // Usamos '/home' en lugar de '/admin/home' porque esa es la ruta real protegida
        $response = $this->get('/home');
        
        $response->assertRedirect('/login'); // Código 302 hacia el login
    }
}