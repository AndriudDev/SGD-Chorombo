<?php

namespace Tests\Feature;

use App\Models\ActaConfidencial;
use App\Models\Auditoria;
use App\Models\Memo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * QA-F4-001/002/003: la purga no deja archivos huérfanos silenciosos ni
 * registros en papelera sin adjunto; las copias legadas en public también
 * se eliminan y un archivo no borrable aborta la purga.
 */
class CorreccionQaF4IntegridadPurgaTest extends TestCase
{
    use RefreshDatabase;

    private User $directora;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directora = $this->userWithRole('directora', 'Dirección');
    }

    public function test_purga_de_acta_con_copia_legada_en_public_elimina_ambos_discos(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $ruta = 'actas/qa-f4-001.pdf';
        Storage::disk('local')->put($ruta, 'contenido local');
        Storage::disk('public')->put($ruta, 'copia legada');

        $acta = $this->crearActaEnBase([
            'titulo' => 'Acta histórica QA-F4-001',
            'archivo' => $ruta,
        ]);
        $acta->delete();

        $this->actingAs($this->directora)
            ->delete(route('papelera.destroy', ['actas', $acta->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('actas_confidenciales', ['id' => $acta->id]);
        Storage::disk('local')->assertMissing($ruta);
        Storage::disk('public')->assertMissing($ruta);

        $this->assertSame(1, Auditoria::query()
            ->where('accion', Auditoria::ACCION_ELIMINAR_DEFINITIVO)
            ->where('modelo_type', $acta->getMorphClass())
            ->where('modelo_id', $acta->id)
            ->count());
    }

    public function test_purga_no_se_confirma_cuando_el_archivo_no_puede_borrarse(): void
    {
        $acta = $this->crearActaEnBase([
            'titulo' => 'Acta QA-F4-002',
            'archivo' => 'actas/qa-f4-002.pdf',
        ]);
        $acta->delete();

        // QA-F4-002: el archivo existe pero el disco no puede borrarlo.
        $adapter = Mockery::mock(Filesystem::class);
        $adapter->shouldReceive('exists')->andReturn(true);
        $adapter->shouldReceive('delete')->andReturn(false);
        Storage::shouldReceive('disk')->andReturn($adapter);

        $this->actingAs($this->directora)
            ->delete(route('papelera.destroy', ['actas', $acta->id]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($acta->fresh()->trashed());
        $this->assertDatabaseMissing('auditorias', [
            'accion' => Auditoria::ACCION_ELIMINAR_DEFINITIVO,
            'modelo_type' => $acta->getMorphClass(),
            'modelo_id' => $acta->id,
        ]);
    }

    public function test_fallo_de_bd_en_force_delete_conserva_documento_y_archivo(): void
    {
        Storage::fake('public');

        $ruta = 'memos/qa-f4-003.pdf';
        Storage::disk('public')->put($ruta, 'contenido de prueba');

        $memo = Memo::create([
            'folio' => 'MEMO-2026-044',
            'tipo' => 'memo',
            'fecha' => Carbon::parse('2026-11-20'),
            'destinatario' => 'Dirección Escolar',
            'asunto' => 'Asunto QA-F4-003',
            'archivo' => $ruta,
            'user_id' => $this->directora->id,
        ]);
        $memo->delete();

        // QA-F4-003: la BD falla en forceDelete; el archivo no debe desaparecer.
        Memo::forceDeleting(fn () => throw new RuntimeException('Fallo inducido de BD (QA-F4-003).'));

        $this->actingAs($this->directora)
            ->delete(route('papelera.destroy', ['memos', $memo->id]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($memo->fresh()->trashed());
        Storage::disk('public')->assertExists($ruta);
    }

    public function test_purga_normal_elimina_registro_y_archivo_y_registra_auditoria(): void
    {
        Storage::fake('public');

        $ruta = 'memos/qa-f4-positivo.pdf';
        Storage::disk('public')->put($ruta, 'contenido de prueba');

        $memo = Memo::create([
            'folio' => 'MEMO-2026-045',
            'tipo' => 'memo',
            'fecha' => Carbon::parse('2026-11-20'),
            'destinatario' => 'Dirección Escolar',
            'asunto' => 'Asunto QA-F4 positivo',
            'archivo' => $ruta,
            'user_id' => $this->directora->id,
        ]);
        $memo->delete();

        $this->actingAs($this->directora)
            ->delete(route('papelera.destroy', ['memos', $memo->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('memos', ['id' => $memo->id]);
        Storage::disk('public')->assertMissing($ruta);

        $this->assertDatabaseHas('auditorias', [
            'accion' => Auditoria::ACCION_ELIMINAR_DEFINITIVO,
            'modelo_type' => $memo->getMorphClass(),
            'modelo_id' => $memo->id,
        ]);
    }

    private function crearActaEnBase(array $extra = []): ActaConfidencial
    {
        return ActaConfidencial::create(array_merge([
            'titulo' => 'Acta QA-F4',
            'categoria' => 'confidencial',
            'ambito' => 'conductual',
            'estudiante_nombre' => 'Estudiante QA',
            'estudiante_run' => '12.345.678-9',
            'apoderado_nombre' => 'Apoderado QA',
            'fecha' => Carbon::parse('2026-11-20'),
            'user_id' => $this->directora->id,
        ], $extra));
    }

    private function userWithRole(string $roleName, string $displayName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => $displayName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
