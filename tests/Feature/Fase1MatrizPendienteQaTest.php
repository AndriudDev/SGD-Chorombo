<?php

namespace Tests\Feature;

use App\Models\Memo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class Fase1MatrizPendienteQaTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $activeDatabase = DB::selectOne('select database() as database_name');

        $this->assertSame('testing', $activeDatabase->database_name);
    }

    public function test_hu01_administracion_asigna_los_tres_roles_aun_no_cubiertos(): void
    {
        $administracion = $this->createRole('administracion', 'Administración');
        $administrador = $this->createUserWithRole($administracion);
        $rolesPendientes = [
            [$administracion, 'Administración', 'qa.matriz.administracion@example.test'],
            [$this->createRole('directora', 'Directora'), 'Dirección', 'qa.matriz.directora@example.test'],
            [$this->createRole('convivencia', 'Convivencia'), 'Convivencia', 'qa.matriz.convivencia@example.test'],
        ];

        foreach ($rolesPendientes as [$role, $nombre, $email]) {
            $response = $this->actingAs($administrador)->post(route('users.store'), [
                'name' => "Usuario QA {$nombre}",
                'email' => $email,
                'password' => Str::random(32),
                'role' => $role->id,
            ]);

            $response->assertRedirect(route('users.index'));
            $response->assertSessionHasNoErrors();

            $usuario = User::where('email', $email)->firstOrFail();

            $this->assertSame([$role->id], $usuario->roles()->pluck('roles.id')->all());
        }
    }

    public function test_hu01_utp_no_accede_al_panel_exclusivo_de_administracion(): void
    {
        $utp = $this->createUserWithRole($this->createRole('utp', 'UTP'));

        $response = $this->actingAs($utp)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_hu01_convivencia_no_accede_al_panel_exclusivo_de_administracion(): void
    {
        $convivencia = $this->createUserWithRole($this->createRole('convivencia', 'Convivencia'));

        $response = $this->actingAs($convivencia)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_hu01_directora_lee_y_descarga_correspondencia(): void
    {
        Storage::fake('public');
        $secretaria = $this->createUserWithRole($this->createRole('secretaria', 'Secretaría'));
        $directora = $this->createUserWithRole($this->createRole('directora', 'Directora'));
        $archivo = 'memos/memo-directora-qa.pdf';
        Storage::disk('public')->put($archivo, 'Contenido ficticio para descarga QA');
        $memo = $this->createMemo($secretaria, [
            'folio' => 'MEM-DIR-2026-001',
            'archivo' => $archivo,
        ]);

        $this->actingAs($directora)
            ->get(route('memos.show', $memo))
            ->assertOk()
            ->assertSee('MEM-DIR-2026-001');
        $this->actingAs($directora)
            ->get(route('memos.download', $memo))
            ->assertOk()
            ->assertDownload('memo-directora-qa.pdf');
    }

    public function test_hu01_utp_lee_correspondencia_general(): void
    {
        $secretaria = $this->createUserWithRole($this->createRole('secretaria', 'Secretaría'));
        $utp = $this->createUserWithRole($this->createRole('utp', 'UTP'));
        $memo = $this->createMemo($secretaria, [
            'folio' => 'MEM-UTP-2026-001',
            'asunto' => 'Documento general ficticio para UTP',
        ]);

        $response = $this->actingAs($utp)->get(route('memos.show', $memo));

        $response->assertOk();
        $response->assertSee('MEM-UTP-2026-001');
        $response->assertSee('Documento general ficticio para UTP');
    }

    public function test_hu01_convivencia_no_accede_a_correspondencia_general(): void
    {
        $convivencia = $this->createUserWithRole($this->createRole('convivencia', 'Convivencia'));

        $response = $this->actingAs($convivencia)->get(route('memos.index'));

        $response->assertForbidden();
    }

    private function createRole(string $name, string $displayName): Role
    {
        return Role::create([
            'name' => $name,
            'display_name' => $displayName,
        ]);
    }

    private function createUserWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createMemo(User $user, array $overrides = []): Memo
    {
        return Memo::create(array_merge([
            'folio' => 'MEM-MATRIZ-2026-001',
            'tipo' => 'memo',
            'fecha' => '2026-09-10',
            'destinatario' => 'Equipo ficticio',
            'asunto' => 'Correspondencia general ficticia',
            'user_id' => $user->id,
        ], $overrides));
    }
}
