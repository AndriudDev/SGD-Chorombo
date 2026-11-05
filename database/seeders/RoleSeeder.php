<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'directora', 'display_name' => 'Dirección'],
            ['name' => 'administracion', 'display_name' => 'Administración'],
            ['name' => 'inspectoria', 'display_name' => 'Inspectoría General'],
            ['name' => 'secretaria', 'display_name' => 'Secretaría de Dirección'],
            ['name' => 'utp', 'display_name' => 'Coordinación Académica o Jefatura Técnica'],
            ['name' => 'convivencia', 'display_name' => 'Convivencia Escolar'],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(['name' => $roleData['name']], $roleData);
        }

        // Create admin user and assign administracion role
        $admin = User::firstOrCreate(
            ['email' => 'admin@sgd-chorombo.cl'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );

        $adminRole = Role::where('name', 'administracion')->first();
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        // Demo users for the remaining roles (same password: password)
        $demoUsers = [
            ['name' => 'Dirección Escolar', 'email' => 'directora@sgd-chorombo.cl', 'role' => 'directora'],
            ['name' => 'Inspectoría General', 'email' => 'inspectoria@sgd-chorombo.cl', 'role' => 'inspectoria'],
            ['name' => 'Secretaría de Dirección', 'email' => 'secretaria@sgd-chorombo.cl', 'role' => 'secretaria'],
            ['name' => 'Coordinación Académica', 'email' => 'utp@sgd-chorombo.cl', 'role' => 'utp'],
            ['name' => 'Convivencia Escolar', 'email' => 'convivencia@sgd-chorombo.cl', 'role' => 'convivencia'],
        ];

        foreach ($demoUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                ['name' => $userData['name'], 'password' => bcrypt('password')]
            );

            $role = Role::where('name', $userData['role'])->first();
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
