<?php

namespace Tests\Feature;

use App\Models\Memo;
use App\Models\PermisoAdministrativo;
use App\Models\Role;
use App\Models\User;
use App\Services\CorrespondenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Fase2MatrizPendienteQaTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $activeDatabase = DB::selectOne('select database() as database_name');

        $this->assertSame('testing', $activeDatabase->database_name);
    }

    public function test_hu02_secretaria_registra_oficio_word_de_15_mb(): void
    {
        Storage::fake('public');
        $secretaria = $this->createUserWithRole('secretaria', 'Secretaría');
        $archivo = UploadedFile::fake()->create(
            'oficio-ficticio.docx',
            15360,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );

        $response = $this->actingAs($secretaria)->post(route('memos.store'), [
            'folio' => 'OFI-WORD-2026-001',
            'tipo' => 'oficio',
            'fecha' => '2026-09-15',
            'destinatario' => 'Unidad ficticia',
            'asunto' => 'Oficio Word ficticio en límite permitido',
            'archivo' => $archivo,
        ]);

        $response->assertRedirect(route('memos.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('memos', [
            'folio' => 'OFI-WORD-2026-001',
            'tipo' => 'oficio',
            'user_id' => $secretaria->id,
        ]);

        $oficio = Memo::where('folio', 'OFI-WORD-2026-001')->firstOrFail();

        $this->assertNotNull($oficio->archivo);
        Storage::disk('public')->assertExists($oficio->archivo);
    }

    public function test_hu02_rechaza_archivo_superior_a_15_mb(): void
    {
        Storage::fake('public');
        $secretaria = $this->createUserWithRole('secretaria', 'Secretaría');

        $response = $this->actingAs($secretaria)
            ->from(route('memos.create'))
            ->post(route('memos.store'), [
                'folio' => 'MEM-GRANDE-2026-001',
                'tipo' => 'memo',
                'fecha' => '2026-09-16',
                'destinatario' => 'Unidad ficticia',
                'asunto' => 'Documento ficticio superior al límite',
                'archivo' => UploadedFile::fake()->create('memo-grande.pdf', 15361, 'application/pdf'),
            ]);

        $response->assertRedirect(route('memos.create'));
        $response->assertSessionHasErrors([
            'archivo' => 'El archivo no debe superar los 15 MB.',
        ]);
        $this->assertDatabaseMissing('memos', [
            'folio' => 'MEM-GRANDE-2026-001',
        ]);
    }

    public function test_hu02_rechaza_extension_distinta_de_pdf_o_word(): void
    {
        Storage::fake('public');
        $secretaria = $this->createUserWithRole('secretaria', 'Secretaría');

        $response = $this->actingAs($secretaria)
            ->from(route('memos.create'))
            ->post(route('memos.store'), [
                'folio' => 'MEM-TXT-2026-001',
                'tipo' => 'memo',
                'fecha' => '2026-09-17',
                'destinatario' => 'Unidad ficticia',
                'asunto' => 'Documento ficticio con extensión inválida',
                'archivo' => UploadedFile::fake()->create('memo-invalido.txt', 100, 'text/plain'),
            ]);

        $response->assertRedirect(route('memos.create'));
        $response->assertSessionHasErrors([
            'archivo' => 'Solo se permiten archivos PDF o Word.',
        ]);
        $this->assertDatabaseMissing('memos', [
            'folio' => 'MEM-TXT-2026-001',
        ]);
    }

    public function test_hu03_rechaza_solicitud_sin_campos_obligatorios(): void
    {
        $secretaria = $this->createUserWithRole('secretaria', 'Secretaría');

        $response = $this->actingAs($secretaria)
            ->from(route('permisos.create'))
            ->post(route('permisos.store'), []);

        $response->assertRedirect(route('permisos.create'));
        $response->assertSessionHasErrors([
            'fecha_inicio' => 'La fecha de inicio es obligatoria.',
            'fecha_fin' => 'La fecha de término es obligatoria.',
            'tipo_jornada' => 'El tipo de jornada es obligatorio.',
            'motivo' => 'El motivo es obligatorio.',
        ]);
        $this->assertDatabaseCount('permisos_administrativos', 0);
    }

    public function test_hu03_utp_registra_solicitud_propia(): void
    {
        $utp = $this->createUserWithRole('utp', 'UTP');

        $response = $this->actingAs($utp)->post(route('permisos.store'), [
            'fecha_inicio' => '2026-09-21',
            'fecha_fin' => '2026-09-21',
            'tipo_jornada' => 'media',
            'motivo' => 'Solicitud ficticia de UTP',
        ]);

        $response->assertRedirect(route('permisos.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('permisos_administrativos', [
            'user_id' => $utp->id,
            'created_by' => $utp->id,
            'tipo_jornada' => 'media',
            'estado' => 'pendiente',
        ]);
    }

    public function test_hu03_convivencia_registra_solicitud_propia(): void
    {
        $convivencia = $this->createUserWithRole('convivencia', 'Convivencia');

        $response = $this->actingAs($convivencia)->post(route('permisos.store'), [
            'fecha_inicio' => '2026-09-22',
            'fecha_fin' => '2026-09-22',
            'tipo_jornada' => 'completa',
            'motivo' => 'Solicitud ficticia de Convivencia',
        ]);

        $response->assertRedirect(route('permisos.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('permisos_administrativos', [
            'user_id' => $convivencia->id,
            'created_by' => $convivencia->id,
            'tipo_jornada' => 'completa',
            'estado' => 'pendiente',
        ]);
    }

    public function test_hu03_administracion_aprueba_y_actualiza_acumulado(): void
    {
        $administrador = $this->createUserWithRole('administracion', 'Administración');
        $funcionario = User::factory()->create();
        $permiso = $this->createPermiso($funcionario, [
            'fecha_inicio' => '2026-09-23',
            'fecha_fin' => '2026-09-23',
            'tipo_jornada' => 'completa',
        ]);

        $response = $this->actingAs($administrador)->post(route('permisos.aprobar', $permiso));

        $response->assertRedirect(route('permisos.index'));
        $response->assertSessionHas('success', 'Permiso aprobado correctamente.');
        $this->assertDatabaseHas('permisos_administrativos', [
            'id' => $permiso->id,
            'estado' => 'aprobado',
            'aprobado_por' => $administrador->id,
        ]);
        $this->assertSame(
            1.0,
            app(CorrespondenciaService::class)->calcularDiasAcumulados($funcionario->id, 2026),
        );
    }

    public function test_hu03_directora_rechaza_y_no_incrementa_acumulado(): void
    {
        $directora = $this->createUserWithRole('directora', 'Directora');
        $funcionario = User::factory()->create();
        $permiso = $this->createPermiso($funcionario, [
            'fecha_inicio' => '2026-09-24',
            'fecha_fin' => '2026-09-24',
            'tipo_jornada' => 'media',
        ]);

        $response = $this->actingAs($directora)->post(route('permisos.rechazar', $permiso));

        $response->assertRedirect(route('permisos.index'));
        $response->assertSessionHas('success', 'Permiso rechazado correctamente.');
        $this->assertDatabaseHas('permisos_administrativos', [
            'id' => $permiso->id,
            'estado' => 'rechazado',
            'aprobado_por' => $directora->id,
        ]);
        $this->assertSame(
            0.0,
            app(CorrespondenciaService::class)->calcularDiasAcumulados($funcionario->id, 2026),
        );
    }

    public function test_hu03_administracion_ve_alerta_visual_de_pendientes(): void
    {
        $administrador = $this->createUserWithRole('administracion', 'Administración');

        $this->assertSupervisorSeesTwoPendingAlerts($administrador);
    }

    public function test_hu03_directora_ve_alerta_visual_de_pendientes(): void
    {
        $directora = $this->createUserWithRole('directora', 'Directora');

        $this->assertSupervisorSeesTwoPendingAlerts($directora);
    }

    public function test_hu03_directora_supervisa_solicitudes_de_distintos_funcionarios(): void
    {
        $directora = $this->createUserWithRole('directora', 'Directora');
        $funcionariaA = User::factory()->create(['name' => 'Funcionaria Ficticia Alfa']);
        $funcionarioB = User::factory()->create(['name' => 'Funcionario Ficticio Beta']);
        $permisoA = $this->createPermiso($funcionariaA, [
            'fecha_inicio' => '2026-09-25',
            'fecha_fin' => '2026-09-25',
        ]);
        $permisoB = $this->createPermiso($funcionarioB, [
            'fecha_inicio' => '2026-09-26',
            'fecha_fin' => '2026-09-26',
        ]);

        $response = $this->actingAs($directora)->get(route('permisos.index'));

        $response->assertOk();
        $permisoIds = $response->viewData('permisos')->getCollection()->pluck('id')->all();
        $this->assertContains($permisoA->id, $permisoIds);
        $this->assertContains($permisoB->id, $permisoIds);
        $response->assertSee('Funcionaria Ficticia Alfa');
        $response->assertSee('Funcionario Ficticio Beta');
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPermiso(User $user, array $overrides = []): PermisoAdministrativo
    {
        return PermisoAdministrativo::create(array_merge([
            'user_id' => $user->id,
            'fecha_inicio' => '2026-09-20',
            'fecha_fin' => '2026-09-20',
            'tipo_jornada' => 'completa',
            'motivo' => 'Solicitud pendiente ficticia',
            'estado' => 'pendiente',
            'created_by' => $user->id,
        ], $overrides));
    }

    private function assertSupervisorSeesTwoPendingAlerts(User $supervisor): void
    {
        $this->createPermiso(User::factory()->create(), [
            'motivo' => 'Solicitud pendiente ficticia uno',
        ]);
        $this->createPermiso(User::factory()->create(), [
            'fecha_inicio' => '2026-09-21',
            'fecha_fin' => '2026-09-21',
            'motivo' => 'Solicitud pendiente ficticia dos',
        ]);

        $response = $this->actingAs($supervisor)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('bg-warning-subtle', false);
        $response->assertSee('>2</span>', false);
    }
}
