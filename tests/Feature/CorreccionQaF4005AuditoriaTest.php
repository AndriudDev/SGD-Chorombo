<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Memo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * QA-F4-005: ninguna operación documental se confirma sin su traza de
 * auditoría; la purga irreversible exige traza y conserva el documento.
 */
class CorreccionQaF4005AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private User $administracion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administracion = $this->userWithRole('administracion', 'Administración');
    }

    public function test_crear_memo_con_auditoria_caida_no_persiste(): void
    {
        $this->romperAuditoria();

        $this->actingAs($this->administracion)
            ->post(route('memos.store'), $this->datosMemo())
            ->assertStatus(500);

        $this->assertDatabaseCount('memos', 0);
        $this->assertDatabaseCount('auditorias', 0);
    }

    public function test_editar_memo_con_auditoria_caida_no_persiste(): void
    {
        $memo = $this->crearMemoEnBase();

        $this->romperAuditoria();

        $this->actingAs($this->administracion)
            ->put(route('memos.update', $memo), $this->datosMemo(['asunto' => 'Asunto modificado']))
            ->assertStatus(500);

        $this->assertSame('Asunto de prueba QA-F4-005', $memo->fresh()->asunto);
    }

    public function test_eliminar_memo_con_auditoria_caida_no_persiste(): void
    {
        $memo = $this->crearMemoEnBase();

        $this->romperAuditoria();

        $this->actingAs($this->administracion)
            ->delete(route('memos.destroy', $memo))
            ->assertStatus(500);

        $this->assertFalse($memo->fresh()->trashed());
    }

    public function test_restaurar_con_auditoria_caida_no_confirma(): void
    {
        $memo = $this->crearMemoEnBase();
        $memo->delete();

        $this->romperAuditoria();

        $this->actingAs($this->administracion)
            ->post(route('papelera.restaurar', ['memos', $memo->id]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($memo->fresh()->trashed());
    }

    public function test_purgar_con_auditoria_caida_conserva_documento_y_archivo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('memos/qa-f4-005.pdf', 'contenido de prueba');

        $memo = $this->crearMemoEnBase(['archivo' => 'memos/qa-f4-005.pdf']);
        $memo->delete();

        $this->romperAuditoria();

        $this->actingAs($this->administracion)
            ->delete(route('papelera.destroy', ['memos', $memo->id]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($memo->fresh()->trashed());
        Storage::disk('public')->assertExists('memos/qa-f4-005.pdf');
    }

    public function test_crear_memo_con_auditoria_operativa_registra_traza(): void
    {
        $this->actingAs($this->administracion)
            ->post(route('memos.store'), $this->datosMemo())
            ->assertRedirect()
            ->assertSessionHas('success');

        $memo = Memo::sole();

        $this->assertDatabaseCount('memos', 1);
        $this->assertDatabaseHas('auditorias', [
            'accion' => Auditoria::ACCION_CREAR,
            'modelo_type' => $memo->getMorphClass(),
            'modelo_id' => $memo->id,
        ]);
    }

    /**
     * Inyecta un fallo en la escritura de la bitácora (QA-F4-005).
     */
    private function romperAuditoria(): void
    {
        Auditoria::creating(fn () => throw new \RuntimeException('Fallo inducido de auditoría (QA-F4-005).'));
    }

    private function crearMemoEnBase(array $extra = []): Memo
    {
        return Memo::create(array_merge($this->datosMemo(['folio' => 'MEMO-2026-001']), $extra, [
            'user_id' => $this->administracion->id,
        ]));
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
            'asunto' => 'Asunto de prueba QA-F4-005',
        ], $extra);
    }

    private function userWithRole(string $roleName, string $displayName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => $displayName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
