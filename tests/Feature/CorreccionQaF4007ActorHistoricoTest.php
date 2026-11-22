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
 * QA-F4-007: la bitácora conserva la identidad histórica del actor aunque
 * su cuenta se elimine (instantánea user_nombre en el momento del evento).
 */
class CorreccionQaF4007ActorHistoricoTest extends TestCase
{
    use RefreshDatabase;

    private User $administracion;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'administracion'], ['display_name' => 'Administración']);
        $this->administracion = User::factory()->create(['name' => 'Funcionaria QA F4-007']);
        $this->administracion->roles()->attach($role);
    }

    public function test_atribucion_sobrevive_a_la_baja_de_la_cuenta(): void
    {
        $this->actingAs($this->administracion)
            ->post(route('memos.store'), $this->datosMemo())
            ->assertRedirect()
            ->assertSessionHas('success');

        $registro = Auditoria::sole();
        $this->assertSame('Funcionaria QA F4-007', $registro->user_nombre);

        $destructora = User::factory()->create();
        $destructora->roles()->attach($this->administracion->roles()->first());

        $this->actingAs($destructora)
            ->delete(route('users.destroy', $this->administracion))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $this->administracion->id]);

        $registro->refresh();
        $this->assertNull($registro->user_id);

        $contenido = $this->actingAs($destructora)
            ->get(route('auditoria.index'))
            ->assertOk()
            ->assertSee('Funcionaria QA F4-007')
            ->getContent();

        // La celda de la bitácora muestra la instantánea, nunca el fallback.
        $this->assertStringContainsString('<td>Funcionaria QA F4-007</td>', $contenido);
    }

    public function test_registros_previos_sin_instantanea_se_muestran_sin_errores(): void
    {
        Auditoria::create([
            'user_id' => null,
            'user_nombre' => null,
            'accion' => Auditoria::ACCION_CREAR,
            'modelo_type' => Memo::class,
            'modelo_id' => 1,
            'modelo_nombre' => 'MEMO-2026-001',
            'detalles' => null,
        ]);

        $this->actingAs($this->administracion)
            ->get(route('auditoria.index'))
            ->assertOk()
            ->assertSee('Usuario eliminado');
    }

    /**
     * @return array<string, mixed>
     */
    private function datosMemo(array $extra = []): array
    {
        return array_merge([
            'tipo' => 'memo',
            'fecha' => Carbon::parse('2026-11-20')->format('Y-m-d'),
            'destinatario' => 'Dirección Escolar',
            'asunto' => 'Asunto de prueba QA-F4-007',
        ], $extra);
    }
}
