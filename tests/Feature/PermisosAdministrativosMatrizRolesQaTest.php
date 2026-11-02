<?php

namespace Tests\Feature;

use App\Models\PermisoAdministrativo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PermisosAdministrativosMatrizRolesQaTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $activeDatabase = DB::selectOne('select database() as database_name');

        $this->assertSame('testing', $activeDatabase->database_name);
    }

    public function test_administracion_ve_solicitudes_de_todos_los_funcionarios(): void
    {
        $administrador = $this->createUserWithRole('administracion', 'Administración');
        $funcionariaA = User::factory()->create(['name' => 'Funcionaria Ficticia Norte']);
        $funcionarioB = User::factory()->create(['name' => 'Funcionario Ficticio Sur']);
        $permisoA = $this->createPermiso($funcionariaA, 'Solicitud ficticia norte');
        $permisoB = $this->createPermiso($funcionarioB, 'Solicitud ficticia sur');

        $response = $this->actingAs($administrador)->get(route('permisos.index'));

        $response->assertOk();
        $permisoIds = $response->viewData('permisos')->getCollection()->pluck('id')->all();
        $this->assertContains($permisoA->id, $permisoIds);
        $this->assertContains($permisoB->id, $permisoIds);
        $response->assertSee('Funcionaria Ficticia Norte');
        $response->assertSee('Funcionario Ficticio Sur');
    }

    public function test_administracion_rechaza_solicitud_de_otro_funcionario(): void
    {
        $administrador = $this->createUserWithRole('administracion', 'Administración');
        $funcionario = User::factory()->create();
        $permiso = $this->createPermiso($funcionario, 'Solicitud ficticia para rechazo administrativo');

        $response = $this->actingAs($administrador)->post(route('permisos.rechazar', $permiso));

        $response->assertRedirect(route('permisos.index'));
        $response->assertSessionHas('success', 'Permiso rechazado correctamente.');
        $this->assertDatabaseHas('permisos_administrativos', [
            'id' => $permiso->id,
            'user_id' => $funcionario->id,
            'estado' => 'rechazado',
            'aprobado_por' => $administrador->id,
        ]);
    }

    public function test_directora_edita_solicitud_de_otro_funcionario(): void
    {
        $directora = $this->createUserWithRole('directora', 'Directora');
        $funcionario = User::factory()->create();
        $permiso = $this->createPermiso($funcionario, 'Motivo ficticio original');

        $this->actingAs($directora)
            ->get(route('permisos.edit', $permiso))
            ->assertOk();
        $response = $this->actingAs($directora)->put(route('permisos.update', $permiso), [
            'fecha_inicio' => '2026-10-05',
            'fecha_fin' => '2026-10-06',
            'tipo_jornada' => 'media',
            'motivo' => 'Motivo ficticio actualizado por Dirección',
        ]);

        $response->assertRedirect(route('permisos.index'));
        $response->assertSessionHas('success', 'Solicitud de permiso actualizada correctamente.');
        $this->assertDatabaseHas('permisos_administrativos', [
            'id' => $permiso->id,
            'user_id' => $funcionario->id,
            'tipo_jornada' => 'media',
            'motivo' => 'Motivo ficticio actualizado por Dirección',
            'estado' => 'pendiente',
        ]);
    }

    public function test_directora_elimina_solicitud_de_otro_funcionario(): void
    {
        $directora = $this->createUserWithRole('directora', 'Directora');
        $funcionario = User::factory()->create();
        $permiso = $this->createPermiso($funcionario, 'Solicitud ficticia para eliminación');

        $response = $this->actingAs($directora)->delete(route('permisos.destroy', $permiso));

        $response->assertRedirect(route('permisos.index'));
        $response->assertSessionHas('success', 'Solicitud de permiso eliminada correctamente.');
        $this->assertDatabaseMissing('permisos_administrativos', [
            'id' => $permiso->id,
        ]);
    }

    public function test_directora_aprueba_solicitud_de_otro_funcionario(): void
    {
        $directora = $this->createUserWithRole('directora', 'Directora');
        $funcionario = User::factory()->create();
        $permiso = $this->createPermiso($funcionario, 'Solicitud ficticia para aprobación');

        $response = $this->actingAs($directora)->post(route('permisos.aprobar', $permiso));

        $response->assertRedirect(route('permisos.index'));
        $response->assertSessionHas('success', 'Permiso aprobado correctamente.');
        $this->assertDatabaseHas('permisos_administrativos', [
            'id' => $permiso->id,
            'user_id' => $funcionario->id,
            'estado' => 'aprobado',
            'aprobado_por' => $directora->id,
        ]);
    }

    public function test_utp_solo_ve_solicitudes_propias(): void
    {
        $utp = $this->createUserWithRole('utp', 'UTP');

        $this->assertUserSeesOnlyOwnPermisos($utp);
    }

    public function test_utp_no_edita_elimina_ni_decide_solicitudes(): void
    {
        $utp = $this->createUserWithRole('utp', 'UTP');

        $this->assertUserCannotManagePermisos($utp);
    }

    public function test_convivencia_solo_ve_solicitudes_propias(): void
    {
        $convivencia = $this->createUserWithRole('convivencia', 'Convivencia');

        $this->assertUserSeesOnlyOwnPermisos($convivencia);
    }

    public function test_convivencia_no_edita_elimina_ni_decide_solicitudes(): void
    {
        $convivencia = $this->createUserWithRole('convivencia', 'Convivencia');

        $this->assertUserCannotManagePermisos($convivencia);
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

    private function createPermiso(User $user, string $motivo): PermisoAdministrativo
    {
        return PermisoAdministrativo::create([
            'user_id' => $user->id,
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-01',
            'tipo_jornada' => 'completa',
            'motivo' => $motivo,
            'estado' => 'pendiente',
            'created_by' => $user->id,
        ]);
    }

    private function assertUserSeesOnlyOwnPermisos(User $user): void
    {
        $otroFuncionario = User::factory()->create();
        $permisoPropio = $this->createPermiso($user, 'Solicitud propia ficticia');
        $permisoAjeno = $this->createPermiso($otroFuncionario, 'Solicitud ajena ficticia');

        $response = $this->actingAs($user)->get(route('permisos.index'));

        $response->assertOk();
        $permisoIds = $response->viewData('permisos')->getCollection()->pluck('id')->all();
        $this->assertContains($permisoPropio->id, $permisoIds);
        $this->assertNotContains($permisoAjeno->id, $permisoIds);
    }

    private function assertUserCannotManagePermisos(User $user): void
    {
        $permiso = $this->createPermiso($user, 'Solicitud propia sin gestión');

        $this->actingAs($user)
            ->get(route('permisos.edit', $permiso))
            ->assertForbidden();
        $this->actingAs($user)
            ->put(route('permisos.update', $permiso), [
                'fecha_inicio' => '2026-10-02',
                'fecha_fin' => '2026-10-02',
                'tipo_jornada' => 'media',
                'motivo' => 'Cambio no autorizado',
            ])
            ->assertForbidden();
        $this->actingAs($user)
            ->delete(route('permisos.destroy', $permiso))
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('permisos.aprobar', $permiso))
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('permisos.rechazar', $permiso))
            ->assertForbidden();

        $this->assertDatabaseHas('permisos_administrativos', [
            'id' => $permiso->id,
            'user_id' => $user->id,
            'motivo' => 'Solicitud propia sin gestión',
            'estado' => 'pendiente',
            'aprobado_por' => null,
        ]);
    }
}
