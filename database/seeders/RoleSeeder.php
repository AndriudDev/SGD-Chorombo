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
            ['name' => 'directora', 'display_name' => 'Directora'],
            ['name' => 'administracion', 'display_name' => 'Administración'],
            ['name' => 'secretaria', 'display_name' => 'Secretaria'],
            ['name' => 'utp', 'display_name' => 'UTP'],
            ['name' => 'convivencia', 'display_name' => 'Convivencia'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(['name' => $roleData['name']], $roleData);
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
            ['name' => 'Secretaría Escolar', 'email' => 'secretaria@sgd-chorombo.cl', 'role' => 'secretaria'],
            ['name' => 'Jefatura UTP', 'email' => 'utp@sgd-chorombo.cl', 'role' => 'utp'],
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