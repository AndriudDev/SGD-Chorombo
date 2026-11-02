<?php

namespace Tests\Feature;

use App\Models\Memo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Fase2ValidacionesQaTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $activeDatabase = DB::selectOne('select database() as database_name');

        $this->assertSame('testing', $activeDatabase->database_name);
    }

    public function test_hu02_rechaza_correspondencia_sin_campos_obligatorios(): void
    {
        $secretaria = $this->createUserWithRole('secretaria', 'Secretaría');

        $response = $this->actingAs($secretaria)
            ->from(route('memos.create'))
            ->post(route('memos.store'), []);

        $response->assertRedirect(route('memos.create'));
        $response->assertSessionHasErrors([
            'folio' => 'El folio es obligatorio.',
            'tipo' => 'El tipo de documento es obligatorio.',
            'fecha' => 'La fecha es obligatoria.',
            'destinatario' => 'El destinatario es obligatorio.',
            'asunto' => 'El asunto es obligatorio.',
        ]);
        $this->assertDatabaseCount('memos', 0);
    }

    public function test_hu02_rechaza_folio_duplicado_en_el_mismo_ano(): void
    {
        $secretaria = $this->createUserWithRole('secretaria', 'Secretaría');
        $folio = 'MEM-QA-2026-001';
        Memo::create([
            'folio' => $folio,
            'tipo' => 'memo',
            'fecha' => '2026-03-10',
            'destinatario' => 'Dirección ficticia',
            'asunto' => 'Documento original ficticio',
            'user_id' => $secretaria->id,
        ]);

        $response = $this->actingAs($secretaria)
            ->from(route('memos.create'))
            ->post(route('memos.store'), [
                'folio' => $folio,
                'tipo' => 'memo',
                'fecha' => '2026-08-15',
                'destinatario' => 'Unidad ficticia',
                'asunto' => 'Documento duplicado ficticio',
            ]);

        $response->assertRedirect(route('memos.create'));
        $response->assertSessionHasErrors([
            'folio' => "El folio '{$folio}' ya existe en el año 2026.",
        ]);
        $this->assertDatabaseCount('memos', 1);
        $this->assertDatabaseMissing('memos', [
            'asunto' => 'Documento duplicado ficticio',
        ]);
    }

    public function test_hu03_rechaza_fecha_final_anterior_a_la_inicial(): void
    {
        $secretaria = $this->createUserWithRole('secretaria', 'Secretaría');

        $response = $this->actingAs($secretaria)
            ->from(route('permisos.create'))
            ->post(route('permisos.store'), [
                'fecha_inicio' => '2026-11-12',
                'fecha_fin' => '2026-11-11',
                'tipo_jornada' => 'completa',
                'motivo' => 'Solicitud ficticia con fechas inválidas',
            ]);

        $response->assertRedirect(route('permisos.create'));
        $response->assertSessionHasErrors([
            'fecha_fin' => 'La fecha de término debe ser igual o posterior a la de inicio.',
        ]);
        $this->assertDatabaseCount('permisos_administrativos', 0);
    }

    public function test_hu03_rechaza_jornada_invalida(): void
    {
        $secretaria = $this->createUserWithRole('secretaria', 'Secretaría');

        $response = $this->actingAs($secretaria)
            ->from(route('permisos.create'))
            ->post(route('permisos.store'), [
                'fecha_inicio' => '2026-11-12',
                'fecha_fin' => '2026-11-12',
                'tipo_jornada' => 'parcial',
                'motivo' => 'Solicitud ficticia con jornada inválida',
            ]);

        $response->assertRedirect(route('permisos.create'));
        $response->assertSessionHasErrors([
            'tipo_jornada' => 'La jornada debe ser completa o media.',
        ]);
        $this->assertDatabaseCount('permisos_administrativos', 0);
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
