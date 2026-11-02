<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PermisosAdministrativosCreacionDelegadaQaTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $activeDatabase = DB::selectOne('select database() as database_name');

        $this->assertSame('testing', $activeDatabase->database_name);
    }

    public function test_administracion_crea_solicitud_para_otro_funcionario(): void
    {
        $administrador = $this->createUserWithRole('administracion', 'Administración');
        $funcionario = User::factory()->create();

        $response = $this->actingAs($administrador)->post(route('permisos.store'), [
            'user_id' => $funcionario->id,
            'fecha_inicio' => '2026-11-03',
            'fecha_fin' => '2026-11-03',
            'tipo_jornada' => 'completa',
            'motivo' => 'Solicitud ficticia creada por Administración',
        ]);

        $response->assertRedirect(route('permisos.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('permisos_administrativos', [
            'user_id' => $funcionario->id,
            'created_by' => $administrador->id,
            'fecha_inicio' => '2026-11-03',
            'fecha_fin' => '2026-11-03',
            'tipo_jornada' => 'completa',
            'motivo' => 'Solicitud ficticia creada por Administración',
            'estado' => 'pendiente',
        ]);
    }

    public function test_directora_crea_solicitud_para_otro_funcionario(): void
    {
        $directora = $this->createUserWithRole('directora', 'Directora');
        $funcionario = User::factory()->create();

        $response = $this->actingAs($directora)->post(route('permisos.store'), [
            'user_id' => $funcionario->id,
            'fecha_inicio' => '2026-11-04',
            'fecha_fin' => '2026-11-04',
            'tipo_jornada' => 'media',
            'motivo' => 'Solicitud ficticia creada por Dirección',
        ]);

        $response->assertRedirect(route('permisos.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('permisos_administrativos', [
            'user_id' => $funcionario->id,
            'created_by' => $directora->id,
            'fecha_inicio' => '2026-11-04',
            'fecha_fin' => '2026-11-04',
            'tipo_jornada' => 'media',
            'motivo' => 'Solicitud ficticia creada por Dirección',
            'estado' => 'pendiente',
        ]);
    }

    private function createUserWithRole(string $name, string $displayName): User
    {
        $role = Role::create([
            'name' => $name,
            'display_name' => $displayName,
        ]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
