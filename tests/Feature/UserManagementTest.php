<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Persona;
use App\Models\Municipio;
use Spatie\Permission\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

class UserManagementTest extends TestCase

{

    use DatabaseTransactions, WithFaker;



    protected $superAdmin;



    protected function setUp(): void

    {

        parent::setUp();



        // Limpia la caché de permisos

        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();



        // Crear roles manualmente en lugar de usar el seeder

        Role::create(['name' => 'Super-Admin', 'guard_name' => 'web']);

        Role::create(['name' => 'Admin-Municipal', 'guard_name' => 'web']);



        // Crear un usuario Super-Admin para las pruebas

        $persona = Persona::factory()->create();

        $this->superAdmin = User::factory()->create([

            'persona_id' => $persona->id,

            'municipio_id' => null,

        ]);

        $this->superAdmin->assignRole('Super-Admin');

    }



    /**

     * @test

     */

    public function super_admin_can_create_new_user_and_person()

    {

        // Autenticar como Super-Admin

        $this->actingAs($this->superAdmin);



        // Crear un municipio necesario para asignar al nuevo usuario

        $municipio = Municipio::factory()->create();

        

        // Obtener el rol a asignar (distinto de Super-Admin)

        $roleToAssign = Role::where('name', 'Admin-Municipal')->first();



        // Datos del nuevo usuario y persona

        $password = 'Password123';

        $userData = [

            'nombre' => $this->faker->firstName,

            'primer_apellido' => $this->faker->lastName,

            'segundo_apellido' => $this->faker->lastName,

            'carnet' => $this->faker->unique()->numerify('########'),

            'expedido' => 'LP',

            'email' => $this->faker->unique()->safeEmail,

            'password' => $password,

            'password_confirmation' => $password,

            'rol_id' => $roleToAssign->id,

            'municipio_id' => $municipio->id,

            'ci_es_indefinido' => '1',

        ];



        // Enviar la petición POST para crear el usuario

        $response = $this->post(route('admin.usuarios.store'), $userData);



        // Verificar la redirección

        $response->assertRedirect(route('admin.usuarios.index'));

        $response->assertSessionHas('success');



        // Verificar que el usuario fue creado en la base de datos

        $this->assertDatabaseHas('users', [

            'email' => $userData['email'],

            'municipio_id' => $municipio->id,

        ]);



        // Verificar que la persona fue creada en la base de datos

        $this->assertDatabaseHas('personas', [

            'carnet' => $userData['carnet'],

            'nombre' => strtoupper($userData['nombre']),

            'primer_apellido' => strtoupper($userData['primer_apellido']),

        ]);



        // Verificar que el usuario tiene el rol asignado

        $newUser = User::where('email', $userData['email'])->first();

        $this->assertTrue($newUser->hasRole('Admin-Municipal'));

    }

}




