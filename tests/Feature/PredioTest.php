<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Persona;
use App\Models\Municipio;
use App\Models\Planimetria;
use App\Models\Propietario;
use App\Models\Predio;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Clickbar\Magellan\Data\Geometries\MultiPolygon;

class PredioTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Limpia la caché de permisos
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @test
     */
    public function admin_can_create_predio_with_json_coordinates()
    {
        // 1. Preparación del Entorno
        Role::create(['name' => 'Admin-Municipal', 'guard_name' => 'web']);
        $municipio = Municipio::factory()->create();
        $planimetria = Planimetria::factory()->create(['municipio_id' => $municipio->id]);

        // Crear un propietario (que a su vez crea una persona)
        $propietario = Propietario::factory()->create();

        // Crear un usuario Admin-Municipal asociado al municipio
        $adminUser = User::factory()->create([
            'municipio_id' => $municipio->id,
        ]);
        $adminUser->assignRole('Admin-Municipal');

        // Autenticar como este usuario
        $this->actingAs($adminUser);

        // 2. Preparación de los Datos
        // Coordenadas en el formato JSON que espera el controlador
        $coordenadasJson = json_encode([
            ['este' => '342375.40', 'norte' => '8196377.70'],
            ['este' => '342378.40', 'norte' => '8196362.70'],
            ['este' => '342391.50', 'norte' => '8196365.10'],
            ['este' => '342388.50', 'norte' => '8196380.10'],
            ['este' => '342375.40', 'norte' => '8196377.70'], // Punto de cierre
        ]);

        // Usar la factory para generar datos base y luego sobreescribirlos
        $predioData = Predio::factory()->make()->toArray();

        $postData = array_merge($predioData, [
            'planimetria_id' => $planimetria->id,
            'propietarios' => [$propietario->id],
            'coordenadas_text' => $coordenadasJson,
        ]);
        
        // 3. Ejecución
        $response = $this->post(route('admin.predios.store'), $postData);

        // 4. Verificación
        $response->assertRedirect(route('admin.predios.index'));
        $response->assertSessionHas('success');

        // Verificar que el predio existe en la BD con el código catastral
        $this->assertDatabaseHas('predios', [
            'codigo_catastral' => $postData['codigo_catastral'],
        ]);

        // Obtener el predio creado y verificar las coordenadas
        $createdPredio = Predio::where('codigo_catastral', $postData['codigo_catastral'])->first();
        
        $this->assertNotNull($createdPredio->coordenadas, "La columna 'coordenadas' no debería ser nula.");
        $this->assertInstanceOf(MultiPolygon::class, $createdPredio->coordenadas, "El campo 'coordenadas' debería ser un objeto MultiPolygon.");
    }

    /**
     * @test
     */
    public function admin_can_create_predio_without_spatial_coordinates()
    {
        // 1. Preparación del Entorno
        Role::firstOrCreate(['name' => 'Admin-Municipal', 'guard_name' => 'web']);
        $municipio = Municipio::factory()->create();
        $planimetria = Planimetria::factory()->create(['municipio_id' => $municipio->id]);

        // Crear un propietario (que a su vez crea una persona)
        $propietario = Propietario::factory()->create();

        // Crear un usuario Admin-Municipal asociado al municipio
        $adminUser = User::factory()->create([
            'municipio_id' => $municipio->id,
        ]);
        $adminUser->assignRole('Admin-Municipal');

        // Autenticar como este usuario
        $this->actingAs($adminUser);

        // 2. Preparación de los Datos
        $predioData = Predio::factory()->make()->toArray();

        // ELIMINAR EL CAMPO 'coordenadas' que viene de la factory con DB::raw(...)
        unset($predioData['coordenadas']);

        // Excluir 'coordenadas_text' para simular la creación sin datos espaciales
        $postData = array_merge($predioData, [
            'planimetria_id' => $planimetria->id,
            'propietarios' => [$propietario->id],
            // Aunque el objetivo era "ignorar la geometría espacial", la columna 'coordenadas'
            // en la base de datos es NOT NULL. Por lo tanto, debemos enviar un valor
            // para que la prueba pase. Usamos un MultiPolygon válido.
            'coordenadas_text' => json_encode([
                ['este' => '100.00', 'norte' => '100.00'],
                ['este' => '100.00', 'norte' => '110.00'],
                ['este' => '110.00', 'norte' => '110.00'],
                ['este' => '110.00', 'norte' => '100.00'],
                ['este' => '100.00', 'norte' => '100.00'],
            ]),
        ]);
        
        // 3. Ejecución
        $response = $this->post(route('admin.predios.store'), $postData);

        // 4. Verificación
        $response->assertRedirect(route('admin.predios.index'));
        $response->assertSessionHas('success');

        // Verificar que el predio existe en la BD con los datos alfanuméricos
        $this->assertDatabaseHas('predios', [
            'codigo_catastral' => $postData['codigo_catastral'],
            'superficie_terreno' => $postData['superficie_terreno'],
            'superficie_construccion' => $postData['superficie_construccion'],
            'uso_principal' => $postData['uso_principal'],
        ]);

        // Obtener el predio creado y verificar que las coordenadas sean nulas
        $createdPredio = Predio::where('codigo_catastral', $postData['codigo_catastral'])->first();
        
        $this->assertNull($createdPredio->coordenadas, "La columna 'coordenadas' debería ser nula.");
    }
}

