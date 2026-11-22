<?php

namespace Tests\Feature;

use App\Models\ReunionComunal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA-F4-004: la cadena «0» es un término de búsqueda válido. Antes, la
 * condición `if ($texto = trim(...))` la trataba como falsy y omitía el
 * filtro, devolviendo documentos no relacionados. Ahora `q=0` filtra por
 * texto; `q` vacía o ausente sigue listando sin filtro y los términos
 * normales (incluida «00») se comportan como cadenas.
 */
class CorreccionQaF4004BusquedaCeroTest extends TestCase
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

    private function crearReunion(array $atributos = []): ReunionComunal
    {
        return ReunionComunal::create(array_merge([
            'titulo' => 'Reunión de prueba',
            'categoria' => 'red_utp',
            'fecha' => '2026-11-04',
            'descripcion' => 'Reunión de prueba de la fase 4',
            'user_id' => $this->directora->id,
        ], $atributos));
    }

    public function test_q_cero_filtra_por_texto_y_no_omite_el_filtro(): void
    {
        $this->crearReunion(['titulo' => 'Reunión bloque 0']);
        $this->crearReunion(['titulo' => 'Reunión red de coordinación']);

        // Solo la reunión que contiene «0» debe coincidir: exactamente 1 resultado.
        $this->actingAs($this->directora)
            ->get(route('buscador.index', ['q' => '0', 'tipo' => 'reunion']))
            ->assertOk()
            ->assertSee('Reunión bloque 0')
            ->assertDontSee('Reunión red de coordinación')
            ->assertSee('align-middle">1</span>', false);
    }

    public function test_q_vacia_o_ausente_lista_sin_filtro_de_texto(): void
    {
        $this->crearReunion(['titulo' => 'Reunión bloque 0']);
        $this->crearReunion(['titulo' => 'Reunión red de coordinación']);

        // Sin q: ambas reuniones visibles.
        $this->actingAs($this->directora)
            ->get(route('buscador.index', ['tipo' => 'reunion']))
            ->assertOk()
            ->assertSee('Reunión bloque 0')
            ->assertSee('Reunión red de coordinación');

        // q vacía: mismo comportamiento, sin filtro de texto.
        $this->actingAs($this->directora)
            ->get(route('buscador.index', ['q' => '', 'tipo' => 'reunion']))
            ->assertOk()
            ->assertSee('Reunión bloque 0')
            ->assertSee('Reunión red de coordinación');
    }

    public function test_q_doble_cero_y_terminos_normales_se_tratan_como_cadenas(): void
    {
        $this->crearReunion(['titulo' => 'Reunión bloque 0']);
        $this->crearReunion(['titulo' => 'Reunión red de coordinación']);

        // «00» es un término real: ninguna reunión lo contiene → sin coincidencias.
        $this->actingAs($this->directora)
            ->get(route('buscador.index', ['q' => '00', 'tipo' => 'reunion']))
            ->assertOk()
            ->assertSee('Sin coincidencias')
            ->assertDontSee('Reunión bloque 0')
            ->assertDontSee('Reunión red de coordinación');

        // Término normal sigue filtrando con normalidad.
        $this->actingAs($this->directora)
            ->get(route('buscador.index', ['q' => 'coordinación', 'tipo' => 'reunion']))
            ->assertOk()
            ->assertSee('Reunión red de coordinación')
            ->assertDontSee('Reunión bloque 0');
    }
}
