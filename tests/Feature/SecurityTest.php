<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Persona;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SecurityTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Limpia la caché de permisos
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Crear roles necesarios
        Role::create(['name' => 'Super-Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Inspector-Tecnico', 'guard_name' => 'web']);
    }

    /**
     * @test
     */
    public function inspector_tecnico_cannot_access_admin_municipios_index()
    {
        // 1. Preparación: Crear un usuario con el rol 'Inspector-Tecnico'
        $persona = Persona::factory()->create();
        $inspectorTecnico = User::factory()->create([
            'persona_id' => $persona->id,
            'municipio_id' => null, // No es necesario para este test de acceso
        ]);
        $inspectorTecnico->assignRole('Inspector-Tecnico');

        // 2. Actuación: Autenticar como 'Inspector-Tecnico' e intentar acceder a admin.municipios.index
        $response = $this->actingAs($inspectorTecnico)
                         ->get(route('admin.municipios.index'));

        // 3. Verificación: La respuesta debe ser 403 (Forbidden)
        $response->assertStatus(403);
    }
}
