<?php

namespace Tests\Feature;

use App\Models\PermisoAdministrativo;
use App\Models\Role;
use App\Models\User;
use App\Services\CorrespondenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PermisoTransicionesTest extends TestCase
{
    use RefreshDatabase;

    private User $funcionario;

    private User $admin;

    private User $directora;

    private User $inspectoria;

    private PermisoAdministrativo $aprobado;

    protected function setUp(): void
    {
        parent::setUp();

        $roleAdmin = Role::create(['name' => 'administracion', 'display_name' => 'Administración']);
        $roleDirectora = Role::create(['name' => 'directora', 'display_name' => 'Dirección']);
        $roleInspectoria = Role::create(['name' => 'inspectoria', 'display_name' => 'Inspectoría General']);
        $roleSecretaria = Role::create(['name' => 'secretaria', 'display_name' => 'Secretaría de Dirección']);

        $this->funcionario = User::factory()->create(['password' => Hash::make('password')]);
        $this->funcionario->roles()->attach($roleSecretaria);

        $this->admin = User::factory()->create(['password' => Hash::make('password')]);
        $this->admin->roles()->attach($roleAdmin);

        $this->directora = User::factory()->create(['password' => Hash::make('password')]);
        $this->directora->roles()->attach($roleDirectora);

        $this->inspectoria = User::factory()->create(['password' => Hash::make('password')]);
        $this->inspectoria->roles()->attach($roleInspectoria);

        $this->aprobado = PermisoAdministrativo::create([
            'user_id' => $this->funcionario->id,
            'fecha_inicio' => Carbon::parse('2026-06-01'),
            'fecha_fin' => Carbon::parse('2026-06-03'),
            'tipo_jornada' => 'completa',
            'motivo' => 'Permiso aprobado QA',
            'estado' => PermisoAdministrativo::ESTADO_APROBADO,
            'aprobado_por' => $this->admin->id,
            'created_by' => $this->funcionario->id,
        ]);
    }

    public function test_rechazar_bloquea_solicitud_aprobada_y_conserva_el_saldo(): void
    {
        $acumuladoAntes = app(CorrespondenciaService::class)
            ->calcularDiasAcumulados($this->funcionario->id, 2026);

        $this->actingAs($this->admin)
            ->from(route('permisos.index'))
            ->post(route('permisos.rechazar', $this->aprobado->id))
            ->assertRedirect(route('permisos.index'))
            ->assertSessionHas('error');

        $this->assertSame(
            PermisoAdministrativo::ESTADO_APROBADO,
            $this->aprobado->fresh()->estado
        );

        $this->assertSame($acumuladoAntes, app(CorrespondenciaService::class)
            ->calcularDiasAcumulados($this->funcionario->id, 2026));
    }

    public function test_rechazar_desde_pendiente_sigue_permitido(): void
    {
        $pendiente = PermisoAdministrativo::create([
            'user_id' => $this->funcionario->id,
            'fecha_inicio' => Carbon::parse('2026-07-01'),
            'fecha_fin' => Carbon::parse('2026-07-01'),
            'tipo_jornada' => 'completa',
            'motivo' => 'Solicitud pendiente QA',
            'estado' => PermisoAdministrativo::ESTADO_PENDIENTE,
            'created_by' => $this->funcionario->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('permisos.rechazar', $pendiente->id))
            ->assertRedirect(route('permisos.index'));

        $this->assertSame(
            PermisoAdministrativo::ESTADO_RECHAZADO,
            $pendiente->fresh()->estado
        );
    }

    public function test_aprobar_bloquea_transiciones_ilegales(): void
    {
        $this->actingAs($this->directora)
            ->from(route('permisos.index'))
            ->post(route('permisos.aprobar', $this->aprobado->id))
            ->assertRedirect(route('permisos.index'))
            ->assertSessionHas('error');

        $this->assertSame(
            PermisoAdministrativo::ESTADO_APROBADO,
            $this->aprobado->fresh()->estado
        );

        $rechazado = PermisoAdministrativo::create([
            'user_id' => $this->funcionario->id,
            'fecha_inicio' => Carbon::parse('2026-08-01'),
            'fecha_fin' => Carbon::parse('2026-08-01'),
            'tipo_jornada' => 'completa',
            'motivo' => 'Solicitud rechazada QA',
            'estado' => PermisoAdministrativo::ESTADO_RECHAZADO,
            'aprobado_por' => $this->admin->id,
            'created_by' => $this->funcionario->id,
        ]);

        $this->actingAs($this->directora)
            ->from(route('permisos.index'))
            ->post(route('permisos.aprobar', $rechazado->id))
            ->assertRedirect(route('permisos.index'))
            ->assertSessionHas('error');

        $this->assertSame(
            PermisoAdministrativo::ESTADO_RECHAZADO,
            $rechazado->fresh()->estado
        );
    }

    public function test_revocar_devuelve_los_dias_al_saldo(): void
    {
        $acumuladoAntes = app(CorrespondenciaService::class)
            ->calcularDiasAcumulados($this->funcionario->id, 2026);

        $this->assertSame(3.0, $acumuladoAntes);

        $this->actingAs($this->directora)
            ->post(route('permisos.revocar', $this->aprobado->id))
            ->assertRedirect(route('permisos.index'))
            ->assertSessionHas('success');

        $this->assertSame(
            PermisoAdministrativo::ESTADO_REVOCADA,
            $this->aprobado->fresh()->estado
        );

        $this->assertSame(0.0, app(CorrespondenciaService::class)
            ->calcularDiasAcumulados($this->funcionario->id, 2026));
    }

    public function test_revocar_solo_lo_permite_direccion_y_administracion(): void
    {
        $this->actingAs($this->inspectoria)
            ->post(route('permisos.revocar', $this->aprobado->id))
            ->assertForbidden();

        $this->actingAs($this->funcionario)
            ->post(route('permisos.revocar', $this->aprobado->id))
            ->assertForbidden();

        $this->assertSame(
            PermisoAdministrativo::ESTADO_APROBADO,
            $this->aprobado->fresh()->estado
        );

        $this->actingAs($this->admin)
            ->post(route('permisos.revocar', $this->aprobado->id))
            ->assertRedirect(route('permisos.index'));

        $this->assertSame(
            PermisoAdministrativo::ESTADO_REVOCADA,
            $this->aprobado->fresh()->estado
        );
    }

    public function test_indice_renderiza_boton_revocar_y_badge_revocada_segun_rol(): void
    {
        // El botón "Revocar" solo se muestra a Dirección/Administración
        // mientras la solicitud siga aprobada.
        $this->actingAs($this->directora)
            ->get(route('permisos.index'))
            ->assertOk()
            ->assertSee('Revocar')
            ->assertDontSee('Revocada');

        $this->actingAs($this->inspectoria)
            ->get(route('permisos.index'))
            ->assertOk()
            ->assertDontSee('Revocar');

        $this->actingAs($this->directora)
            ->post(route('permisos.revocar', $this->aprobado->id))
            ->assertRedirect(route('permisos.index'));

        $this->actingAs($this->inspectoria)
            ->get(route('permisos.index'))
            ->assertOk()
            ->assertSee('Revocada')
            ->assertDontSee('Revocar');
    }

    public function test_revocar_solo_admite_solicitudes_aprobadas(): void
    {
        $pendiente = PermisoAdministrativo::create([
            'user_id' => $this->funcionario->id,
            'fecha_inicio' => Carbon::parse('2026-09-01'),
            'fecha_fin' => Carbon::parse('2026-09-01'),
            'tipo_jornada' => 'completa',
            'motivo' => 'Otra solicitud pendiente QA',
            'estado' => PermisoAdministrativo::ESTADO_PENDIENTE,
            'created_by' => $this->funcionario->id,
        ]);

        $this->actingAs($this->admin)
            ->from(route('permisos.index'))
            ->post(route('permisos.revocar', $pendiente->id))
            ->assertRedirect(route('permisos.index'))
            ->assertSessionHas('error');

        $this->assertSame(
            PermisoAdministrativo::ESTADO_PENDIENTE,
            $pendiente->fresh()->estado
        );
    }

    public function test_aprobar_registra_al_aprobador_en_el_camino_de_auditoria(): void
    {
        $pendiente = PermisoAdministrativo::create([
            'user_id' => $this->funcionario->id,
            'fecha_inicio' => Carbon::parse('2026-05-01'),
            'fecha_fin' => Carbon::parse('2026-05-01'),
            'tipo_jornada' => 'completa',
            'motivo' => 'Solicitud para aprobar QA',
            'estado' => PermisoAdministrativo::ESTADO_PENDIENTE,
            'created_by' => $this->funcionario->id,
        ]);

        $this->actingAs($this->directora)
            ->post(route('permisos.aprobar', $pendiente->id))
            ->assertRedirect(route('permisos.index'));

        $permiso = $pendiente->fresh();

        $this->assertSame(PermisoAdministrativo::ESTADO_APROBADO, $permiso->estado);
        $this->assertSame($this->directora->id, $permiso->aprobado_por);

        $transicion = $permiso->ultimaTransicion;
        $this->assertSame($this->directora->id, $transicion->user_id);
        $this->assertSame(PermisoAdministrativo::ESTADO_PENDIENTE, $transicion->estado_anterior);
        $this->assertSame(PermisoAdministrativo::ESTADO_APROBADO, $transicion->estado_nuevo);
    }

    public function test_rechazar_no_usa_aprobado_por_y_registra_al_rechazador(): void
    {
        $pendiente = PermisoAdministrativo::create([
            'user_id' => $this->funcionario->id,
            'fecha_inicio' => Carbon::parse('2026-05-02'),
            'fecha_fin' => Carbon::parse('2026-05-02'),
            'tipo_jornada' => 'completa',
            'motivo' => 'Solicitud para rechazar QA',
            'estado' => PermisoAdministrativo::ESTADO_PENDIENTE,
            'created_by' => $this->funcionario->id,
        ]);

        $this->actingAs($this->inspectoria)
            ->post(route('permisos.rechazar', $pendiente->id))
            ->assertRedirect(route('permisos.index'));

        $permiso = $pendiente->fresh();

        $this->assertSame(PermisoAdministrativo::ESTADO_RECHAZADO, $permiso->estado);
        $this->assertNull($permiso->aprobado_por, 'aprobado_por solo identifica al aprobador, no al rechazador.');

        $transicion = $permiso->ultimaTransicion;
        $this->assertSame($this->inspectoria->id, $transicion->user_id);
        $this->assertSame(PermisoAdministrativo::ESTADO_PENDIENTE, $transicion->estado_anterior);
        $this->assertSame(PermisoAdministrativo::ESTADO_RECHAZADO, $transicion->estado_nuevo);
    }

    public function test_revocar_conserva_al_aprobador_original_y_registra_al_revocador(): void
    {
        $this->actingAs($this->directora)
            ->post(route('permisos.revocar', $this->aprobado->id))
            ->assertRedirect(route('permisos.index'));

        $permiso = $this->aprobado->fresh();

        $this->assertSame(PermisoAdministrativo::ESTADO_REVOCADA, $permiso->estado);
        $this->assertSame(
            $this->admin->id,
            $permiso->aprobado_por,
            'La revocación no debe sobrescribir al aprobador original.'
        );

        $transicion = $permiso->ultimaTransicion;
        $this->assertSame($this->directora->id, $transicion->user_id);
        $this->assertSame(PermisoAdministrativo::ESTADO_APROBADO, $transicion->estado_anterior);
        $this->assertSame(PermisoAdministrativo::ESTADO_REVOCADA, $transicion->estado_nuevo);
    }

    public function test_indice_muestra_al_aprobador_y_al_responsable_de_la_revocacion(): void
    {
        $pendiente = PermisoAdministrativo::create([
            'user_id' => $this->funcionario->id,
            'fecha_inicio' => Carbon::parse('2026-05-03'),
            'fecha_fin' => Carbon::parse('2026-05-03'),
            'tipo_jornada' => 'completa',
            'motivo' => 'Solicitud para mostrar identidades QA',
            'estado' => PermisoAdministrativo::ESTADO_PENDIENTE,
            'created_by' => $this->funcionario->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('permisos.aprobar', $pendiente->id))
            ->assertRedirect(route('permisos.index'));

        $this->actingAs($this->directora)
            ->post(route('permisos.revocar', $pendiente->id))
            ->assertRedirect(route('permisos.index'));

        $this->actingAs($this->directora)
            ->get(route('permisos.index'))
            ->assertOk()
            ->assertSee('Aprobada por '.$this->admin->name)
            ->assertSee('Revocada por '.$this->directora->name);
    }
}
