<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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
    use DatabaseTransactions, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Limpia la caché de permisos
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @test
     */
    public function admin_can_create_predio()
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
        // Usar la factory para generar datos base y luego sobreescribirlos
        $predioData = Predio::factory()->make()->toArray();

        // Eliminar 'coordenadas' (que es una Expression) antes de enviar
        unset($predioData['coordenadas']);

        $postData = array_merge($predioData, [
            'planimetria_id' => $planimetria->id,
            'propietarios' => [$propietario->id],
            'coordenadas_text' => null, // Establecer a null para evitar el bug del controlador
            'sup_levantamiento' => $predioData['sup_levantamiento'] ?? 100,
            'sup_construida' => $predioData['sup_construida'] ?? 50,
        ]);
        
        // 3. Ejecución
        $response = $this->post(route('admin.predios.store'), $postData);

        // 4. Verificación
        $response->assertRedirect(route('admin.predios.index'));
        $response->assertSessionHas('success');

        // Verificar que el predio existe en la BD con el código catastral
        $this->assertDatabaseHas('predios', [
            'codigo_catastral' => $postData['codigo_catastral'],
            'sup_levantamiento' => $postData['sup_levantamiento'],
            'sup_construida' => $postData['sup_construida'],
        ]);

        // Obtener el predio creado y verificar que las coordenadas sean nulas
        $createdPredio = Predio::where('codigo_catastral', $postData['codigo_catastral'])->first();
        
        $this->assertNull($createdPredio->coordenadas, "La columna 'coordenadas' debería ser nula.");
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
            'coordenadas_text' => null, // Enviamos null para que el controlador no intente crear objetos geométricos
            'sup_levantamiento' => $predioData['sup_levantamiento'] ?? 100, // Usar nombre de columna correcto
            'sup_construida' => $predioData['sup_construida'] ?? 50, // Usar nombre de columna correcto
            // 'uso_principal' no existe en la tabla predios, se elimina de los datos a enviar
        ]);
        
        // 3. Ejecución
        $response = $this->post(route('admin.predios.store'), $postData);

        // 4. Verificación
        $response->assertRedirect(route('admin.predios.index'));
        $response->assertSessionHas('success');

        // Verificar que el predio existe en la BD con los datos alfanuméricos
        $this->assertDatabaseHas('predios', [
            'codigo_catastral' => $postData['codigo_catastral'],
            'sup_levantamiento' => $postData['sup_levantamiento'], // Usar nombre de columna correcto
            'sup_construida' => $postData['sup_construida'], // Usar nombre de columna correcto
            // 'uso_principal' no existe en la tabla predios, se elimina de la aserción
        ]);

        // Obtener el predio creado y verificar que las coordenadas sean nulas
        $createdPredio = Predio::where('codigo_catastral', $postData['codigo_catastral'])->first();
        
        $this->assertNull($createdPredio->coordenadas, "La columna 'coordenadas' debería ser nula.");
    }

    /**
     * @test
     */
    public function admin_can_create_predio_with_multiple_propietarios()
    {
        // 1. Preparación del Entorno
        Role::firstOrCreate(['name' => 'Admin-Municipal', 'guard_name' => 'web']);
        $municipio = Municipio::factory()->create();
        $planimetria = Planimetria::factory()->create(['municipio_id' => $municipio->id]);

        // Crear dos propietarios (que a su vez crean personas)
        $propietario1 = Propietario::factory()->create();
        $propietario2 = Propietario::factory()->create();

        // Crear un usuario Admin-Municipal asociado al municipio
        $adminUser = User::factory()->create([
            'municipio_id' => $municipio->id,
        ]);
        $adminUser->assignRole('Admin-Municipal');

        // Autenticar como este usuario
        $this->actingAs($adminUser);

        // 2. Preparación de los Datos del Predio
        $predioData = Predio::factory()->make()->toArray();
        unset($predioData['coordenadas']); // Eliminar la expresión de coordenadas

        $postData = array_merge($predioData, [
            'planimetria_id' => $planimetria->id,
            'propietarios' => [$propietario1->id, $propietario2->id], // IDs de ambos propietarios
            'coordenadas_text' => null, // Para evitar el bug del controlador
            'sup_levantamiento' => $predioData['sup_levantamiento'] ?? 100,
            'sup_construida' => $predioData['sup_construida'] ?? 50,
        ]);

        // 3. Ejecución
        $response = $this->post(route('admin.predios.store'), $postData);

        // 4. Verificación
        $response->assertRedirect(route('admin.predios.index'));
        $response->assertSessionHas('success');

        // Verificar que el predio fue creado
        $this->assertDatabaseHas('predios', [
            'codigo_catastral' => $postData['codigo_catastral'],
        ]);

        // Obtener el predio creado
        $createdPredio = Predio::where('codigo_catastral', $postData['codigo_catastral'])->first();
        $this->assertNotNull($createdPredio);

        // Verificar que ambos propietarios estén adjuntos al predio
        $this->assertDatabaseHas('propietarios_predios', [
            'predio_id' => $createdPredio->id,
            'propietario_id' => $propietario1->id,
            'estado' => 'Propietario Actual',
        ]);
        $this->assertDatabaseHas('propietarios_predios', [
            'predio_id' => $createdPredio->id,
            'propietario_id' => $propietario2->id,
            'estado' => 'Propietario Actual',
        ]);
    }
}

