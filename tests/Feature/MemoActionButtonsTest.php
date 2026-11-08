<?php

namespace Tests\Feature;

use App\Models\Memo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MemoActionButtonsTest extends TestCase
{
    use RefreshDatabase;

    private User $autor;

    private User $otraSecretaria;

    private User $administracion;

    private Memo $memoAjeno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->autor = $this->userWithRole('secretaria', 'Secretaria');
        $this->otraSecretaria = $this->userWithRole('secretaria', 'Secretaria');
        $this->administracion = $this->userWithRole('administracion', 'Administración');

        $this->memoAjeno = Memo::create([
            'folio' => 'MEMO-2026-001',
            'tipo' => 'memo',
            'fecha' => Carbon::parse('2026-11-01'),
            'destinatario' => 'Dirección',
            'asunto' => 'Asunto de prueba OBS-01',
            'user_id' => $this->autor->id,
        ]);
    }

    public function test_index_no_muestra_editar_eliminar_de_un_memo_ajeno(): void
    {
        $this->actingAs($this->otraSecretaria)
            ->get(route('memos.index'))
            ->assertOk()
            ->assertSee(route('memos.show', $this->memoAjeno->id), false)
            ->assertDontSee(route('memos.edit', $this->memoAjeno->id), false)
            ->assertDontSee($this->confirmacionDeEliminacion(), false);
    }

    public function test_index_muestra_editar_eliminar_al_autor_del_memo(): void
    {
        $this->actingAs($this->autor)
            ->get(route('memos.index'))
            ->assertOk()
            ->assertSee(route('memos.edit', $this->memoAjeno->id), false)
            ->assertSee($this->confirmacionDeEliminacion(), false);
    }

    public function test_index_muestra_editar_eliminar_a_administracion(): void
    {
        $this->actingAs($this->administracion)
            ->get(route('memos.index'))
            ->assertOk()
            ->assertSee(route('memos.edit', $this->memoAjeno->id), false)
            ->assertSee($this->confirmacionDeEliminacion(), false);
    }

    public function test_show_no_muestra_editar_eliminar_al_no_autor(): void
    {
        $this->actingAs($this->otraSecretaria)
            ->get(route('memos.show', $this->memoAjeno->id))
            ->assertOk()
            ->assertDontSee(route('memos.edit', $this->memoAjeno->id), false)
            ->assertDontSee($this->confirmacionDeEliminacion(), false);
    }

    public function test_show_muestra_editar_eliminar_al_autor_del_memo(): void
    {
        $this->actingAs($this->autor)
            ->get(route('memos.show', $this->memoAjeno->id))
            ->assertOk()
            ->assertSee(route('memos.edit', $this->memoAjeno->id), false)
            ->assertSee($this->confirmacionDeEliminacion(), false);
    }

    private function userWithRole(string $roleName, string $displayName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => $displayName]);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    private function confirmacionDeEliminacion(): string
    {
        return '¿Está seguro de eliminar el memo/oficio «'.$this->memoAjeno->folio.'»?';
    }
}
