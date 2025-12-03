<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tramite;
use App\Models\Predio;
use App\Models\Persona;
use App\Models\TramiteTipo;
use App\Models\TramiteEstado;
use App\Models\Municipio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

class MLIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure required roles exist
        Role::firstOrCreate(['name' => 'Admin-Municipal', 'guard_name' => 'web']);
    }

    public function test_ml_api_success_integration()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $user->assignRole('Admin-Municipal');

        $municipio = Municipio::first() ?? Municipio::factory()->create();
        $predio = Predio::factory()->create(['municipio_id' => $municipio->id]);
        $solicitante = Persona::factory()->create();
        
        $estado = TramiteEstado::firstOrCreate(['nombre' => 'INGRESADO'], ['color_ui' => 'primary']);
        $tipo = TramiteTipo::firstOrCreate(['nombre' => 'Tipo Test'], ['descripcion' => 'Test']);

        $tramite = Tramite::create([
            'predio_id' => $predio->id,
            'municipio_id' => $predio->municipio_id,
            'usuario_id' => $user->id,
            'solicitante_id' => $solicitante->id,
            'tramite_tipo_id' => $tipo->id,
            'fecha_conclusion' => null, // Inicializar antes de estado_id
            'estado_id' => $estado->id,
            'fecha_ingreso' => now(),
            'codigo_acceso' => 'ML-TEST',
        ]);

        // 2. Mock ML API Response
        $fakeResponse = [
            'modelo_riesgo' => 'Random Forest',
            'nivel_riesgo' => 'Alto',
            'probabilidad_bajo' => 10.0,
            'probabilidad_medio' => 20.0,
            'probabilidad_alto' => 70.0,
            'factores_identificados' => ['Múltiples propietarios', 'Superficie muy grande'],
            'color_riesgo' => 'danger',
            'dias_prediccion' => 45
        ];

        Http::fake([
            '*/predict' => Http::response($fakeResponse, 200),
        ]);

        // 3. Act
        $response = $this->actingAs($user)
                         ->get(route('admin.tramites.show', $tramite));

        // 4. Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.tramites.show');
        
        // Verify view data contains the prediction
        $response->assertViewHas('predictions', function ($predictions) {
            return $predictions['nivel_riesgo'] === 'Alto' && 
                   $predictions['dias_prediccion'] === 45 &&
                   in_array('Múltiples propietarios', $predictions['factores_identificados']);
        });
    }

    public function test_ml_api_failure_graceful_degradation()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $user->assignRole('Admin-Municipal');

        $municipio = Municipio::first() ?? Municipio::factory()->create();
        $predio = Predio::factory()->create(['municipio_id' => $municipio->id]);
        $solicitante = Persona::factory()->create();
        
        $estado = TramiteEstado::firstOrCreate(['nombre' => 'INGRESADO'], ['color_ui' => 'primary']);
        $tipo = TramiteTipo::firstOrCreate(['nombre' => 'Tipo Test'], ['descripcion' => 'Test']);

        $tramite = Tramite::create([
            'predio_id' => $predio->id,
            'municipio_id' => $predio->municipio_id,
            'usuario_id' => $user->id,
            'solicitante_id' => $solicitante->id,
            'tramite_tipo_id' => $tipo->id,
            'fecha_conclusion' => null, // Inicializar antes de estado_id
            'estado_id' => $estado->id,
            'fecha_ingreso' => now(),
            'codigo_acceso' => 'ML-FAIL',
        ]);

        // 2. Mock ML API Error (500 Internal Server Error)
        Http::fake([
            '*/predict' => Http::response(['error' => 'Server Error'], 500),
        ]);

        // 3. Act
        $response = $this->actingAs($user)
                         ->get(route('admin.tramites.show', $tramite));

        // 4. Assert
        $response->assertStatus(200); // Page should still load
        $response->assertViewIs('admin.tramites.show');

        // Verify view data contains the fallback prediction
        $response->assertViewHas('predictions', function ($predictions) {
            return $predictions['modelo_riesgo'] === 'Sistema de respaldo' &&
                   $predictions['nivel_riesgo'] === 'Medio'; // Default fallback value
        });

        // Verify basic tramite data is still present
        $response->assertSee($tramite->tipo->nombre);
        $response->assertSee($tramite->predio->codigo_catastral);
    }
}
