<?php

namespace Tests\Feature;

use App\Models\Memo;
use App\Models\PermisoAdministrativo;
use App\Models\Role;
use App\Models\User;
use App\Services\CorrespondenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Fase2QaTest extends TestCase
{
    use RefreshDatabase;

    private User $secretariaA;

    private User $secretariaB;

    private User $admin;

    private PermisoAdministrativo $permisoDeA;

    private Memo $memoDeA;

    protected function setUp(): void
    {
        parent::setUp();

        $roleSec = Role::create(['name' => 'secretaria', 'display_name' => 'Secretaria']);
        $roleAdmin = Role::create(['name' => 'administracion', 'display_name' => 'Administración']);

        $this->secretariaA = User::factory()->create(['password' => Hash::make('password')]);
        $this->secretariaA->roles()->attach($roleSec);

        $this->secretariaB = User::factory()->create(['password' => Hash::make('password')]);
        $this->secretariaB->roles()->attach($roleSec);

        $this->admin = User::factory()->create(['password' => Hash::make('password')]);
        $this->admin->roles()->attach($roleAdmin);

        // Create a permiso owned by secretariaA
        $this->permisoDeA = PermisoAdministrativo::create([
            'user_id' => $this->secretariaA->id,
            'fecha_inicio' => Carbon::parse('2025-06-01'),
            'fecha_fin' => Carbon::parse('2025-06-03'),
            'tipo_jornada' => 'completa',
            'motivo' => 'Permiso médico',
            'estado' => 'pendiente',
            'created_by' => $this->secretariaA->id,
        ]);

        // Create a memo owned by secretariaA
        $this->memoDeA = Memo::create([
            'folio' => 'MEMO-2025-001',
            'tipo' => 'memo',
            'fecha' => Carbon::parse('2025-06-01'),
            'destinatario' => 'Dirección',
            'asunto' => 'Solicitud de recursos',
            'user_id' => $this->secretariaA->id,
        ]);
    }

    // ── Test: El módulo de permisos está abierto al equipo directivo; cada uno gestiona lo propio (HU-03) ──

    public function test_secretaria_can_access_permisos_module_and_requests_for_herself(): void
    {
        $this->actingAs($this->secretariaA)
            ->get(route('permisos.index'))
            ->assertOk()
            ->assertSee('Permisos Administrativos');

        $this->actingAs($this->secretariaA)
            ->get(route('permisos.create'))
            ->assertOk();

        $this->actingAs($this->secretariaA)
            ->from(route('permisos.index'))
            ->post(route('permisos.store'), [
                'fecha_inicio' => '2025-10-01',
                'fecha_fin' => '2025-10-01',
                'tipo_jornada' => 'media',
                'motivo' => 'Nueva solicitud propia',
            ])
            ->assertRedirect(route('permisos.index'));

        $nuevo = PermisoAdministrativo::where('created_by', $this->secretariaA->id)
            ->where('motivo', 'Nueva solicitud propia')
            ->first();

        $this->assertNotNull($nuevo);
        $this->assertSame($this->secretariaA->id, (int) $nuevo->user_id, 'Secretaría solo puede solicitar permisos para sí misma.');
    }

    public function test_secretaria_cannot_edit_delete_or_approve_permisos(): void
    {
        $this->actingAs($this->secretariaA)
            ->get(route('permisos.edit', $this->permisoDeA->id))
            ->assertForbidden();

        $this->actingAs($this->secretariaA)
            ->put(route('permisos.update', $this->permisoDeA->id), [
                'fecha_inicio' => '2025-08-01',
                'fecha_fin' => '2025-08-02',
                'tipo_jornada' => 'media',
                'motivo' => 'Cambio de fechas',
            ])
            ->assertForbidden();

        $this->actingAs($this->secretariaA)
            ->delete(route('permisos.destroy', $this->permisoDeA->id))
            ->assertForbidden();

        $this->actingAs($this->secretariaA)
            ->post(route('permisos.aprobar', $this->permisoDeA->id))
            ->assertForbidden();

        $this->actingAs($this->secretariaA)
            ->post(route('permisos.rechazar', $this->permisoDeA->id))
            ->assertForbidden();
    }

    public function test_directora_can_access_permisos_module(): void
    {
        $roleDirectora = Role::create(['name' => 'directora', 'display_name' => 'Directora']);
        $directora = User::factory()->create(['password' => Hash::make('password')]);
        $directora->roles()->attach($roleDirectora);

        $this->actingAs($directora)
            ->get(route('permisos.index'))
            ->assertOk()
            ->assertSee('Permisos Administrativos');
    }

    // ── Test: Administración SÍ puede modificar cualquier permiso ──

    public function test_admin_can_update_any_permiso(): void
    {
        $this->actingAs($this->admin)
            ->put(route('permisos.update', $this->permisoDeA->id), [
                'fecha_inicio' => '2025-09-01',
                'fecha_fin' => '2025-09-02',
                'tipo_jornada' => 'completa',
                'motivo' => 'Actualización administrativa',
            ])
            ->assertRedirect(route('permisos.index'));
    }

    public function test_admin_can_delete_any_permiso(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('permisos.destroy', $this->permisoDeA->id))
            ->assertRedirect(route('permisos.index'));
    }

    public function test_admin_can_edit_any_permiso(): void
    {
        $this->actingAs($this->admin)
            ->get(route('permisos.edit', $this->permisoDeA->id))
            ->assertOk();
    }

    // ── Test: La base de datos NO se modifica tras un 403 ──

    public function test_database_unchanged_after_forbidden_update(): void
    {
        $motivoOriginal = $this->permisoDeA->motivo;

        $this->actingAs($this->secretariaB)
            ->put(route('permisos.update', $this->permisoDeA->id), [
                'fecha_inicio' => '2025-12-01',
                'fecha_fin' => '2025-12-02',
                'tipo_jornada' => 'media',
                'motivo' => 'Modificación ilegítima',
            ])
            ->assertForbidden();

        $permiso = PermisoAdministrativo::find($this->permisoDeA->id);
        $this->assertEquals($motivoOriginal, $permiso->motivo, 'Los campos no deben cambiar tras un 403 en la BD.');
        $this->assertEquals($this->secretariaA->id, $permiso->user_id, 'El user_id no debe cambiar tras un 403.');
    }

    public function test_database_unchanged_after_forbidden_delete(): void
    {
        $this->actingAs($this->secretariaB)
            ->delete(route('permisos.destroy', $this->permisoDeA->id))
            ->assertForbidden();

        $permiso = PermisoAdministrativo::find($this->permisoDeA->id);
        $this->assertNotNull($permiso, 'El registro no debe eliminarse tras un 403.');
    }

    // ── Test: Secretaría NO puede modificar memo ajeno ──

    public function test_secretaria_cannot_update_other_users_memo(): void
    {
        $this->actingAs($this->secretariaB)
            ->put(route('memos.update', $this->memoDeA->id), [
                'folio' => 'MEMO-2025-999',
                'tipo' => 'memo',
                'fecha' => '2025-11-01',
                'destinatario' => 'Otra persona',
                'asunto' => 'Intento de modificación ajena',
            ])
            ->assertForbidden();
    }

    public function test_secretaria_cannot_delete_other_users_memo(): void
    {
        $this->actingAs($this->secretariaB)
            ->delete(route('memos.destroy', $this->memoDeA->id))
            ->assertForbidden();
    }

    public function test_secretaria_cannot_edit_other_users_memo(): void
    {
        $this->fromRoute('memos.index')
            ->actingAs($this->secretariaB)
            ->get(route('memos.edit', $this->memoDeA->id))
            ->assertRedirect(route('memos.index'))
            ->assertSessionHas('error', 'No tienes permiso para editar este memo.');
    }

    // ── Test: Secretaría SÍ puede modificar su propio memo ──

    public function test_secretaria_can_update_own_memo(): void
    {
        $this->actingAs($this->secretariaA)
            ->put(route('memos.update', $this->memoDeA->id), [
                'folio' => 'MEMO-2025-002',
                'tipo' => 'memo',
                'fecha' => '2025-10-15',
                'destinatario' => 'Coordinación',
                'asunto' => 'Actualización de asunto',
            ])
            ->assertRedirect(route('memos.index'));
    }

    public function test_secretaria_can_delete_own_memo(): void
    {
        $this->actingAs($this->secretariaA)
            ->delete(route('memos.destroy', $this->memoDeA->id))
            ->assertRedirect(route('memos.index'));
    }

    public function test_secretaria_can_edit_own_memo(): void
    {
        $this->actingAs($this->secretariaA)
            ->get(route('memos.edit', $this->memoDeA->id))
            ->assertOk();
    }

    // ── Test: Administración SÍ puede modificar cualquier memo ──

    public function test_admin_can_update_any_memo(): void
    {
        $this->actingAs($this->admin)
            ->put(route('memos.update', $this->memoDeA->id), [
                'folio' => 'OFICIO-2025-001',
                'tipo' => 'oficio',
                'fecha' => '2025-12-01',
                'destinatario' => 'Comunidad',
                'asunto' => 'Comunicado administrativo',
            ])
            ->assertRedirect(route('memos.index'));
    }

    public function test_admin_can_delete_any_memo(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('memos.destroy', $this->memoDeA->id))
            ->assertRedirect(route('memos.index'));
    }

    public function test_admin_can_edit_any_memo(): void
    {
        $this->actingAs($this->admin)
            ->get(route('memos.edit', $this->memoDeA->id))
            ->assertOk();
    }

    // ── Test: La base de datos NO se modifica tras un 403 ──

    public function test_database_unchanged_after_forbidden_memo_update(): void
    {
        $asuntoOriginal = $this->memoDeA->asunto;

        $this->actingAs($this->secretariaB)
            ->put(route('memos.update', $this->memoDeA->id), [
                'folio' => 'MEMO-2025-888',
                'tipo' => 'memo',
                'fecha' => '2025-12-31',
                'destinatario' => 'Alguien más',
                'asunto' => 'Modificación ilegítima de memo',
            ])
            ->assertForbidden();

        $memo = Memo::find($this->memoDeA->id);
        $this->assertEquals($asuntoOriginal, $memo->asunto, 'Los campos del memo no deben cambiar tras un 403.');
    }

    public function test_database_unchanged_after_forbidden_memo_delete(): void
    {
        $this->actingAs($this->secretariaB)
            ->delete(route('memos.destroy', $this->memoDeA->id))
            ->assertForbidden();

        $memo = Memo::find($this->memoDeA->id);
        $this->assertNotNull($memo, 'El memo no debe eliminarse tras un 403.');
    }

    // ── Test: Secretaría SÍ puede crear un memo ──

    public function test_secretaria_can_create_memo(): void
    {
        $this->actingAs($this->secretariaA)
            ->post(route('memos.store'), [
                'folio' => 'MEMO-2025-100',
                'tipo' => 'memo',
                'fecha' => '2025-10-20',
                'destinatario' => 'Equipo docente',
                'asunto' => 'Recordatorio de reunión',
            ])
            ->assertRedirect(route('memos.index'));
    }

    // ── Test: El acumulado anual cuenta medias jornadas (HU-03 / QA-HU03-03) ──

    public function test_acumulado_anual_cuenta_media_jornada_como_medio_dia(): void
    {
        PermisoAdministrativo::create([
            'user_id' => $this->secretariaA->id,
            'fecha_inicio' => Carbon::parse('2026-10-26'),
            'fecha_fin' => Carbon::parse('2026-10-26'),
            'tipo_jornada' => 'media',
            'motivo' => 'Permiso médico media jornada',
            'estado' => 'aprobado',
            'aprobado_por' => $this->admin->id,
            'created_by' => $this->admin->id,
        ]);

        $this->assertSame(0.5, (new CorrespondenciaService)->calcularDiasAcumulados($this->secretariaA->id, 2026));

        $this->actingAs($this->admin)
            ->get(route('permisos.index', ['year' => 2026]))
            ->assertOk()
            ->assertSee('0,5');
    }

    public function test_acumulado_anual_suma_medias_y_completas_solo_aprobadas(): void
    {
        PermisoAdministrativo::create([
            'user_id' => $this->secretariaA->id,
            'fecha_inicio' => Carbon::parse('2026-02-01'),
            'fecha_fin' => Carbon::parse('2026-02-02'),
            'tipo_jornada' => 'completa',
            'motivo' => 'Permiso completo',
            'estado' => 'aprobado',
            'aprobado_por' => $this->admin->id,
            'created_by' => $this->admin->id,
        ]);

        PermisoAdministrativo::create([
            'user_id' => $this->secretariaA->id,
            'fecha_inicio' => Carbon::parse('2026-03-01'),
            'fecha_fin' => Carbon::parse('2026-03-02'),
            'tipo_jornada' => 'media',
            'motivo' => 'Dos medias jornadas',
            'estado' => 'aprobado',
            'aprobado_por' => $this->admin->id,
            'created_by' => $this->admin->id,
        ]);

        PermisoAdministrativo::create([
            'user_id' => $this->secretariaA->id,
            'fecha_inicio' => Carbon::parse('2026-04-01'),
            'fecha_fin' => Carbon::parse('2026-04-01'),
            'tipo_jornada' => 'media',
            'motivo' => 'Pendiente, no debe sumar',
            'estado' => 'pendiente',
            'created_by' => $this->secretariaA->id,
        ]);

        $this->assertSame(3.0, (new CorrespondenciaService)->calcularDiasAcumulados($this->secretariaA->id, 2026));
    }
}
