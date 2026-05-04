<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*$role = Role::firstOrCreate(['name' => 'Super-Admin']);
        $permission = Permission::firstOrCreate(['name' => 'admin.ver-menu']);
        
        $role->givePermissionTo($permission);*/

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crea los roles
        Role::firstOrCreate(['name' => 'Super-Admin']);
        Role::firstOrCreate(['name' => 'Admin-Municipal']);
        Role::firstOrCreate(['name' => 'Asesor-Legal']);      
        Role::firstOrCreate(['name' => 'Inspector-Tecnico']);
    }
}