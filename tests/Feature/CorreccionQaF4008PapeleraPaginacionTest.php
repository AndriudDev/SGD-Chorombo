<?php

namespace Tests\Feature;

use App\Models\Memo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * QA-F4-008: la papelera paginaba con `limit(50)` por módulo, dejando
 * irrecuperable el documento 51 (el más antiguo). Ahora cada sección pagina
 * con su propio parámetro (`page_<modulo>`), el más antiguo es accesible en
 * la página 2 y no hay duplicados entre páginas.
 */
class CorreccionQaF4008PapeleraPaginacionTest extends TestCase
{
    use RefreshDatabase;

    private User $directora;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directora = $this->crearUsuarioConRol('directora', 'Dirección');
    }

    private function crearUsuarioConRol(string $rol, string $display): User
    {
        $role = Role::create(['name' => $rol, 'display_name' => $display]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * Crea $cantidad memos eliminados con `deleted_at` estrictamente
     * creciente en `folio` y en `deleted_at` (el folio más alto es el más
     * recientemente eliminado y debe aparecer primero).
     */
    private function crearMemosEliminados(int $cantidad): void
    {
        $base = Carbon::parse('2026-10-01 10:00:00');

        for ($i = 0; $i < $cantidad; $i++) {
            $memo = Memo::create([
                'folio' => sprintf('MEMO-PAG-%04d', $i),
                'tipo' => 'memo',
                'fecha' => '2026-09-01',
                'destinatario' => 'Dirección',
                'asunto' => "Asunto de prueba {$i}",
                'user_id' => $this->directora->id,
            ]);

            $memo->delete();

            // Timestamps distintos y deterministas: el update por query builder
            // no dispara eventos de modelo ni toca la auditoría.
            Memo::withTrashed()
                ->whereKey($memo->id)
                ->update(['deleted_at' => $base->copy()->addSeconds($i)]);
        }
    }

    public function test_papelera_pagina_mas_alla_de_50_documentos_sin_duplicados(): void
    {
        $this->crearMemosEliminados(51);

        // Página 1: los 50 más recientes; el documento 51 (el más antiguo) no aparece.
        $this->actingAs($this->directora)
            ->get(route('papelera.index'))
            ->assertOk()
            ->assertSee('MEMO-PAG-0050')
            ->assertDontSee('MEMO-PAG-0000')
            ->assertSee('rounded-pill">51</span>', false);

        // Página 2: el documento 51 es accesible, sin duplicar la página 1.
        $this->actingAs($this->directora)
            ->get(route('papelera.index', ['page_memos' => 2]))
            ->assertOk()
            ->assertSee('MEMO-PAG-0000')
            ->assertDontSee('MEMO-PAG-0050')
            ->assertSee('page_memos=1');
    }

    public function test_pagina_fuera_de_rango_se_acota_a_la_ultima_pagina(): void
    {
        $this->crearMemosEliminados(51);

        $this->actingAs($this->directora)
            ->get(route('papelera.index', ['page_memos' => 99]))
            ->assertOk()
            ->assertSee('MEMO-PAG-0000')
            ->assertDontSee('MEMO-PAG-0050');
    }
}
