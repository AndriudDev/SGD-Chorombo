<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Memo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * QA-F4-006: restaurar genera exactamente una acción «restaurar»; el cambio
 * de deleted_at ya no se clasifica como «editar» con detalle técnico.
 */
class CorreccionQaF4006RestauracionTest extends TestCase
{
    use RefreshDatabase;

    private User $administracion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administracion = $this->userWithRole('administracion', 'Administración');
    }

    public function test_restaurar_registra_unicamente_la_accion_restaurar(): void
    {
        $memo = Memo::create([
            'folio' => 'MEMO-2026-042',
            'tipo' => 'memo',
            'fecha' => Carbon::parse('2026-11-20'),
            'destinatario' => 'Dirección Escolar',
            'asunto' => 'Asunto de prueba QA-F4-006',
            'user_id' => $this->administracion->id,
        ]);

        $memo->delete();

        $this->actingAs($this->administracion)
            ->post(route('papelera.restaurar', ['memos', $memo->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $trazas = Auditoria::query()
            ->where('modelo_type', $memo->getMorphClass())
            ->where('modelo_id', $memo->id)
            ->pluck('accion');

        $this->assertEqualsCanonicalizing(
            [Auditoria::ACCION_CREAR, Auditoria::ACCION_ELIMINAR, Auditoria::ACCION_RESTAURAR],
            $trazas->all(),
        );

        // QA-F4-006: la restauración no produce una entrada «Edición».
        $this->assertDatabaseMissing('auditorias', [
            'accion' => Auditoria::ACCION_EDITAR,
            'modelo_type' => $memo->getMorphClass(),
            'modelo_id' => $memo->id,
        ]);

        $this->assertFalse($memo->fresh()->trashed());
    }

    public function test_edicion_real_del_contenido_sigue_registrando_edicion(): void
    {
        $memo = Memo::create([
            'folio' => 'MEMO-2026-043',
            'tipo' => 'memo',
            'fecha' => Carbon::parse('2026-11-20'),
            'destinatario' => 'Dirección Escolar',
            'asunto' => 'Asunto original QA-F4-006',
            'user_id' => $this->administracion->id,
        ]);

        $memo->update(['asunto' => 'Asunto modificado QA-F4-006']);

        $this->assertDatabaseHas('auditorias', [
            'accion' => Auditoria::ACCION_EDITAR,
            'modelo_type' => $memo->getMorphClass(),
            'modelo_id' => $memo->id,
        ]);
    }

    private function userWithRole(string $roleName, string $displayName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => $displayName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
