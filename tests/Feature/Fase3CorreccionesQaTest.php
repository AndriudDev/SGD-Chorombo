<?php

namespace Tests\Feature;

use App\Models\ActaConfidencial;
use App\Models\Citacion;
use App\Models\ReunionComunal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Fase3CorreccionesQaTest extends TestCase
{
    use RefreshDatabase;

    private User $convivencia;

    private User $secretaria;

    private User $utp;

    private User $inspectoria;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->convivencia = $this->crearUsuarioConRol('convivencia', 'Convivencia');
        $this->secretaria = $this->crearUsuarioConRol('secretaria', 'Secretaria');
        $this->utp = $this->crearUsuarioConRol('utp', 'UTP');
        $this->inspectoria = $this->crearUsuarioConRol('inspectoria', 'Inspectoría General');
    }

    private function crearUsuarioConRol(string $rol, string $display): User
    {
        $role = Role::create(['name' => $rol, 'display_name' => $display]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    // ── QA-F3-002: reunión sin adjunto ──

    public function test_reunion_sin_adjunto_no_ofrece_descarga_y_responde_404(): void
    {
        $reunion = ReunionComunal::create([
            'titulo' => 'Reunión sin adjunto',
            'categoria' => 'red_utp',
            'fecha' => '2026-11-10',
            'archivo' => null,
            'user_id' => $this->utp->id,
        ]);

        $this->actingAs($this->utp)
            ->get(route('comunales.index'))
            ->assertOk()
            ->assertDontSee(route('comunales.download', $reunion));

        $this->actingAs($this->utp)
            ->get(route('comunales.download', $reunion))
            ->assertNotFound();
    }

    // ── QA-F3-005: citación sin adjunto ──

    public function test_citacion_sin_adjunto_responde_404_en_ruta_directa(): void
    {
        $citacion = Citacion::create([
            'estudiante_nombre' => 'Alumno Sin Adjunto',
            'estudiante_run' => '44.444.444-4',
            'apoderado_nombre' => 'Apoderado Sin Adjunto',
            'funcionario_cita' => 'Secretaría Escolar',
            'fecha' => '2026-11-11',
            'hora' => '10:00',
            'motivo' => 'conductual',
            'estado' => 'programada',
            'archivo' => null,
            'user_id' => $this->secretaria->id,
        ]);

        $this->actingAs($this->secretaria)
            ->get(route('citaciones.download', $citacion))
            ->assertNotFound();
    }

    // ── QA-F3-001: archivo histórico del acta en el disco público ──

    public function test_archivo_de_acta_historica_se_traslada_al_disco_privado_al_descargar(): void
    {
        $acta = ActaConfidencial::create([
            'titulo' => 'Acta Histórica',
            'categoria' => 'confidencial',
            'ambito' => 'conductual',
            'estudiante_nombre' => 'Alumno Histórico',
            'estudiante_run' => '66.666.666-6',
            'apoderado_nombre' => 'Apoderado Histórico',
            'fecha' => '2026-10-01',
            'archivo' => 'actas/acta-historica.pdf',
            'user_id' => $this->convivencia->id,
        ]);
        Storage::disk('public')->put('actas/acta-historica.pdf', 'contenido historico');

        $this->actingAs($this->convivencia)
            ->get(route('actas.download', $acta))
            ->assertOk();

        Storage::disk('local')->assertExists('actas/acta-historica.pdf');
        Storage::disk('public')->assertMissing('actas/acta-historica.pdf');

        // La segunda descarga ya sirve desde el disco privado
        $this->actingAs($this->convivencia)
            ->get(route('actas.download', $acta))
            ->assertOk();

        // Sin archivo en ningún disco: 404, nunca 500
        $actaPerdida = ActaConfidencial::create([
            'titulo' => 'Acta Sin Archivo',
            'categoria' => 'confidencial',
            'ambito' => 'conductual',
            'estudiante_nombre' => 'Alumno Perdido',
            'estudiante_run' => '77.777.777-7',
            'apoderado_nombre' => 'Apoderado Perdido',
            'fecha' => '2026-10-02',
            'archivo' => 'actas/perdida.pdf',
            'user_id' => $this->convivencia->id,
        ]);

        $this->actingAs($this->convivencia)
            ->get(route('actas.download', $actaPerdida))
            ->assertNotFound();
    }

    // ── QA-F3-003 / QA-F3-004: Inspectoría sin enlaces a módulos que resuelven 403 ──

    public function test_inspectoria_no_ve_enlaces_a_citaciones_ni_a_reuniones_comunales(): void
    {
        $this->actingAs($this->inspectoria)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('citaciones.index'))
            ->assertDontSee(route('comunales.index'));

        $this->actingAs($this->inspectoria)
            ->get(route('citaciones.index'))
            ->assertForbidden();

        $this->actingAs($this->inspectoria)
            ->get(route('comunales.index'))
            ->assertForbidden();

        // Los roles con acceso consultivo siguen viendo los accesos
        $this->actingAs($this->secretaria)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('citaciones.index'))
            ->assertSee(route('comunales.index'));
    }
}
