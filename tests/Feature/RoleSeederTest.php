<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_actualiza_el_catalogo_de_cargos_hu01(): void
    {
        Role::create(['name' => 'directora', 'display_name' => 'Directora']);
        Role::create(['name' => 'utp', 'display_name' => 'UTP']);

        $this->seed(RoleSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'inspectoria', 'display_name' => 'Inspectoría General']);
        $this->assertDatabaseHas('roles', ['name' => 'directora', 'display_name' => 'Dirección']);
        $this->assertDatabaseHas('roles', ['name' => 'secretaria', 'display_name' => 'Secretaría de Dirección']);
        $this->assertDatabaseHas('roles', [
            'name' => 'utp',
            'display_name' => 'Coordinación Académica o Jefatura Técnica',
        ]);
        $this->assertDatabaseHas('roles', ['name' => 'convivencia', 'display_name' => 'Convivencia Escolar']);
        $this->assertSame(6, Role::count());
    }

    public function test_seeder_crea_la_cuenta_demo_de_inspectoria_asignada_a_su_rol(): void
    {
        $this->seed(RoleSeeder::class);

        $inspectoria = User::where('email', 'inspectoria@sgd-chorombo.cl')->firstOrFail();

        $this->assertTrue($inspectoria->hasRole('inspectoria'));
        $this->assertSame(6, User::count());
    }
}
