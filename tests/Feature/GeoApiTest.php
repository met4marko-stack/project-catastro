<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Predio;
use App\Models\User;
use App\Models\Municipio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;

class GeoApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Asegurar que exista un rol 'Admin-Municipal' para los tests.
        Role::firstOrCreate(['name' => 'Admin-Municipal', 'guard_name' => 'web']);
    }

    public function test_can_find_predio_by_codigo_catastral()
    {
        // 1. Setup
        $user = User::factory()->create();
        $user->assignRole('Admin-Municipal');

        // Asegurar que el municipio existe para evitar problemas de UniqueConstraintViolation.
        $municipio = Municipio::first() ?? Municipio::factory()->create();

        $predio = Predio::factory()->create([
            'codigo_catastral' => 'TEST-001',
            'municipio_id' => $municipio->id,
        ]);

        // 2. Actuación
        $response = $this->actingAs($user)
                         ->getJson(route('admin.predios.buscar', ['codigo_catastral' => 'TEST-001']));

        // 3. Verificación
        $response->assertStatus(200);
        
        // La estructura real es tipo GeoJSON con 'data'
        $response->assertJson([
            'data' => [
                'codigo_catastral' => 'TEST-001',
            ]
        ]);
    }

    public function test_cannot_find_nonexistent_predio()
    {
        // 1. Setup
        $user = User::factory()->create();
        $user->assignRole('Admin-Municipal');

        // 2. Actuación
        $response = $this->actingAs($user)
                         ->getJson(route('admin.predios.buscar', ['codigo_catastral' => 'NO-EXISTE']));

        // 3. Verificación
        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Código Catastral no encontrado.',
        ]);
    }
}
