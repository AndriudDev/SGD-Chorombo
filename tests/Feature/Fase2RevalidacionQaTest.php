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

class Fase2RevalidacionQaTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $activeDatabase = DB::selectOne('select database() as database_name');

        $this->assertSame('testing', $activeDatabase->database_name);
    }

    public function test_hu02_01_secretaria_registra_memo_valido_con_pdf(): void
    {
        Storage::fake('public');
        $secretaria = $this->userWithRole('secretaria', 'Secretaría');

        $response = $this->actingAs($secretaria)->post(route('memos.store'), [
            'folio' => 'MEM-001',
            'tipo' => 'memo',
            'fecha' => '2026-10-25',
            'destinatario' => 'Dirección Escolar',
            'asunto' => 'Memo ficticio para revisión QA',
            'archivo' => UploadedFile::fake()->create('memo-prueba.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('memos.index'));
        $this->assertDatabaseHas('memos', [
            'folio' => 'MEM-001',
            'tipo' => 'memo',
            'user_id' => $secretaria->id,
        ]);

        $memo = Memo::where('folio', 'MEM-001')->firstOrFail();
        $this->assertNotNull($memo->archivo);
        Storage::disk('public')->assertExists($memo->archivo);
    }

    public function test_hu02_02_lista_un_memo_existente_sin_error(): void
    {
        $secretaria = $this->userWithRole('secretaria', 'Secretaría');
        Memo::create([
            'folio' => 'MEM-002',
            'tipo' => 'oficio',
            'fecha' => '2026-10-25',
            'destinatario' => 'Departamento Provincial',
            'asunto' => 'Oficio ficticio para listado QA',
            'user_id' => $secretaria->id,
        ]);

        $response = $this->actingAs($secretaria)->get(route('memos.index'));

        $response->assertOk();
        $response->assertSee('MEM-002');
        $response->assertSee('Departamento Provincial');
    }

    public function test_hu03_01_registra_permiso_de_dia_completo_como_pendiente(): void
    {
        $funcionario = $this->userWithRole('secretaria', 'Secretaría');

        $response = $this->actingAs($funcionario)->post(route('permisos.store'), [
            'fecha_inicio' => '2026-10-26',
            'fecha_fin' => '2026-10-26',
            'tipo_jornada' => 'completa',
            'motivo' => 'Solicitud ficticia para revisión QA',
        ]);

        $response->assertRedirect(route('permisos.index'));
        $this->assertDatabaseHas('permisos_administrativos', [
            'user_id' => $funcionario->id,
            'fecha_inicio' => '2026-10-26',
            'fecha_fin' => '2026-10-26',
            'tipo_jornada' => 'completa',
            'estado' => 'pendiente',
        ]);
    }

    public function test_hu03_02_lista_solo_solicitudes_propias_del_funcionario(): void
    {
        $funcionario = $this->userWithRole('secretaria', 'Secretaría');
        $funcionario->update(['name' => 'Funcionaria Propia QA']);
        $otroFuncionario = User::factory()->create(['name' => 'Funcionaria Ajena QA']);

        $permisoPropio = $this->createPermiso($funcionario, 'Solicitud propia QA');
        $permisoAjeno = $this->createPermiso($otroFuncionario, 'Solicitud ajena QA', [
            'fecha_inicio' => '2026-11-03',
            'fecha_fin' => '2026-11-03',
            'tipo_jornada' => 'media',
        ]);

        $response = $this->actingAs($funcionario)->get(route('permisos.index'));

        $response->assertOk();

        $permisoIds = $response->viewData('permisos')->getCollection()->pluck('id')->all();

        $this->assertContains($permisoPropio->id, $permisoIds);
        $this->assertNotContains($permisoAjeno->id, $permisoIds);
        $response->assertSee('Funcionaria Propia QA');
        $response->assertSee('26/10/2026');
        $response->assertDontSee('Funcionaria Ajena QA');
        $response->assertDontSee('03/11/2026');
    }

    public function test_hu03_03_media_jornada_aprobada_aporta_medio_dia(): void
    {
        $funcionario = User::factory()->create();
        $this->createPermiso($funcionario, 'Media jornada QA', [
            'tipo_jornada' => 'media',
            'estado' => 'aprobado',
        ]);

        $acumulado = app(CorrespondenciaService::class)
            ->calcularDiasAcumulados($funcionario->id, 2026);

        $this->assertSame(0.5, $acumulado);
    }

    public function test_hu03_04_funcionario_no_puede_editar_solicitud_ajena_por_url(): void
    {
        $funcionario = $this->userWithRole('secretaria', 'Secretaría');
        $otroFuncionario = User::factory()->create();
        $permisoAjeno = $this->createPermiso($otroFuncionario, 'Motivo original QA', [
            'tipo_jornada' => 'media',
        ]);

        $response = $this->actingAs($funcionario)->put(
            route('permisos.update', $permisoAjeno),
            [
                'fecha_inicio' => '2026-10-27',
                'fecha_fin' => '2026-10-27',
                'tipo_jornada' => 'completa',
                'motivo' => 'Modificación no autorizada QA',
            ],
        );

        $this->assertDatabaseHas('permisos_administrativos', [
            'id' => $permisoAjeno->id,
            'user_id' => $otroFuncionario->id,
            'created_by' => $otroFuncionario->id,
            'fecha_inicio' => '2026-10-26',
            'fecha_fin' => '2026-10-26',
            'tipo_jornada' => 'media',
            'motivo' => 'Motivo original QA',
            'estado' => 'pendiente',
        ]);
        $response->assertForbidden();
    }

    private function userWithRole(string $name, string $displayName): User
    {
        $role = Role::create([
            'name' => $name,
            'display_name' => $displayName,
        ]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    private function createPermiso(User $user, string $motivo, array $overrides = []): PermisoAdministrativo
    {
        return PermisoAdministrativo::create(array_merge([
            'user_id' => $user->id,
            'fecha_inicio' => '2026-10-26',
            'fecha_fin' => '2026-10-26',
            'tipo_jornada' => 'completa',
            'motivo' => $motivo,
            'estado' => 'pendiente',
            'created_by' => $user->id,
        ], $overrides));
    }
}
