<?php

namespace Tests\Feature;

use App\Models\ActaConfidencial;
use App\Models\Citacion;
use App\Models\ReunionComunal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Fase3QaTest extends TestCase
{
    use RefreshDatabase;

    private User $convivencia;

    private User $secretaria;

    private User $directora;

    private User $utp;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->convivencia = $this->crearUsuarioConRol('convivencia', 'Convivencia');
        $this->secretaria = $this->crearUsuarioConRol('secretaria', 'Secretaria');
        $this->directora = $this->crearUsuarioConRol('directora', 'Directora');
        $this->utp = $this->crearUsuarioConRol('utp', 'UTP');
        $this->admin = $this->crearUsuarioConRol('administracion', 'Administración');
    }

    private function crearUsuarioConRol(string $rol, string $display): User
    {
        $role = Role::create(['name' => $rol, 'display_name' => $display]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    // ── HU-04: Citaciones ──

    public function test_emitentes_registran_citacion_y_roles_directivos_la_consultan(): void
    {
        $this->actingAs($this->secretaria)
            ->get(route('citaciones.create'))
            ->assertOk()
            ->assertSee('Nueva Citación');

        $this->actingAs($this->secretaria)
            ->from(route('citaciones.index'))
            ->post(route('citaciones.store'), [
                'estudiante_nombre' => 'Alumno Uno',
                'estudiante_run' => '11.111.111-1',
                'apoderado_nombre' => 'Madre del Alumno Uno',
                'funcionario_cita' => 'Secretaría Escolar',
                'fecha' => '2026-11-05',
                'hora' => '10:30',
                'motivo' => 'conductual',
            ])
            ->assertRedirect(route('citaciones.index'));

        $citacion = Citacion::where('estudiante_run', '11.111.111-1')->first();
        $this->assertNotNull($citacion);
        $this->assertSame('programada', $citacion->estado);
        $this->assertSame($this->secretaria->id, $citacion->user_id);

        $this->actingAs($this->utp)
            ->get(route('citaciones.index'))
            ->assertOk()
            ->assertSee('Alumno Uno');

        $this->actingAs($this->admin)
            ->get(route('citaciones.index'))
            ->assertOk()
            ->assertSee('Alumno Uno');

        $this->actingAs($this->directora)
            ->get(route('citaciones.show', $citacion))
            ->assertOk()
            ->assertSee('Madre del Alumno Uno');
    }

    public function test_solo_roles_emitentes_pueden_gestionar_citaciones(): void
    {
        $citacion = Citacion::create([
            'estudiante_nombre' => 'Alumno Dos',
            'estudiante_run' => '22.222.222-2',
            'apoderado_nombre' => 'Padre del Alumno Dos',
            'funcionario_cita' => 'Convivencia Escolar',
            'fecha' => '2026-11-06',
            'hora' => '11:00',
            'motivo' => 'pedagogico',
            'estado' => 'programada',
            'user_id' => $this->convivencia->id,
        ]);

        $this->actingAs($this->utp)->get(route('citaciones.create'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('citaciones.create'))->assertForbidden();

        $payload = [
            'estudiante_nombre' => 'Alumno Dos',
            'estudiante_run' => '22.222.222-2',
            'apoderado_nombre' => 'Padre del Alumno Dos',
            'funcionario_cita' => 'UTP',
            'fecha' => '2026-11-06',
            'hora' => '11:00',
            'motivo' => 'pedagogico',
        ];
        $this->actingAs($this->utp)->post(route('citaciones.store'), $payload)->assertForbidden();
        $this->actingAs($this->admin)
            ->delete(route('citaciones.destroy', $citacion))
            ->assertForbidden();

        $this->assertDatabaseHas('citaciones', ['id' => $citacion->id]);
    }

    public function test_seguimiento_de_estado_de_citacion(): void
    {
        $citacion = Citacion::create([
            'estudiante_nombre' => 'Alumno Tres',
            'estudiante_run' => '33.333.333-3',
            'apoderado_nombre' => 'Apoderado Tres',
            'funcionario_cita' => 'Convivencia Escolar',
            'fecha' => '2026-11-07',
            'hora' => '09:15',
            'motivo' => 'conductual',
            'estado' => 'programada',
            'user_id' => $this->convivencia->id,
        ]);

        $this->actingAs($this->convivencia)
            ->patch(route('citaciones.estado', $citacion), ['estado' => 'asistio'])
            ->assertRedirect(route('citaciones.show', $citacion));

        $this->assertSame('asistio', $citacion->fresh()->estado);

        // Estado inválido: se rechaza y no cambia el registro
        $this->actingAs($this->secretaria)
            ->from(route('citaciones.show', $citacion))
            ->patch(route('citaciones.estado', $citacion), ['estado' => 'inventado'])
            ->assertSessionHasErrors('estado');

        $this->assertSame('asistio', $citacion->fresh()->estado);
    }

    public function test_filtros_de_citaciones_por_estado_motivo_y_busqueda(): void
    {
        Citacion::create([
            'estudiante_nombre' => 'Alumno Uno',
            'estudiante_run' => '11.111.111-1',
            'apoderado_nombre' => 'Madre Uno',
            'funcionario_cita' => 'Secretaría',
            'fecha' => '2026-11-05',
            'hora' => '10:00',
            'motivo' => 'conductual',
            'estado' => 'programada',
            'user_id' => $this->secretaria->id,
        ]);
        Citacion::create([
            'estudiante_nombre' => 'Alumno Dos',
            'estudiante_run' => '22.222.222-2',
            'apoderado_nombre' => 'Padre Dos',
            'funcionario_cita' => 'Secretaría',
            'fecha' => '2026-11-06',
            'hora' => '10:00',
            'motivo' => 'pedagogico',
            'estado' => 'asistio',
            'user_id' => $this->secretaria->id,
        ]);

        $this->actingAs($this->directora)
            ->get(route('citaciones.index', ['estado' => 'programada']))
            ->assertOk()
            ->assertSee('Alumno Uno')
            ->assertDontSee('Alumno Dos');

        $this->actingAs($this->directora)
            ->get(route('citaciones.index', ['motivo' => 'pedagogico']))
            ->assertOk()
            ->assertSee('Alumno Dos')
            ->assertDontSee('Alumno Uno');

        $this->actingAs($this->directora)
            ->get(route('citaciones.index', ['search' => '22.222.222-2']))
            ->assertOk()
            ->assertSee('Alumno Dos')
            ->assertDontSee('Alumno Uno');
    }

    public function test_adjunto_de_citacion_se_almacena_y_descarga(): void
    {
        $this->actingAs($this->convivencia)
            ->post(route('citaciones.store'), [
                'estudiante_nombre' => 'Alumno Cinco',
                'estudiante_run' => '55.555.555-5',
                'apoderado_nombre' => 'Apoderado Cinco',
                'funcionario_cita' => 'Convivencia Escolar',
                'fecha' => '2026-11-08',
                'hora' => '15:45',
                'motivo' => 'administrativo',
                'archivo' => UploadedFile::fake()->create('colilla.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect(route('citaciones.index'));

        $citacion = Citacion::where('estudiante_run', '55.555.555-5')->first();
        $this->assertNotNull($citacion->archivo);
        Storage::disk('public')->assertExists($citacion->archivo);

        $this->actingAs($this->utp)
            ->get(route('citaciones.download', $citacion))
            ->assertOk();
    }

    // ── HU-05: Actas confidenciales ──

    public function test_convivencia_y_directora_cargan_actas_confidenciales_restringidas(): void
    {
        $this->actingAs($this->convivencia)
            ->post(route('actas.store'), [
                'titulo' => 'Acta Acuerdo Convivencia Uno',
                'categoria' => 'confidencial',
                'ambito' => 'conductual',
                'estudiante_nombre' => 'Alumno Uno',
                'estudiante_run' => '11.111.111-1',
                'apoderado_nombre' => 'Madre Uno',
                'fecha' => '2026-10-10',
                'archivo' => UploadedFile::fake()->create('acta.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('actas.index'));

        $this->assertDatabaseHas('actas_confidenciales', [
            'titulo' => 'Acta Acuerdo Convivencia Uno',
            'categoria' => 'confidencial',
        ]);

        $this->actingAs($this->directora)
            ->get(route('actas.index'))
            ->assertOk()
            ->assertSee('Acta Acuerdo Convivencia Uno');

        // Acceso estricto: Secretaría y Administración quedan fuera (HU-05)
        $this->actingAs($this->secretaria)->get(route('actas.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('actas.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('actas.create'))->assertForbidden();
    }

    public function test_utp_consulta_solo_actas_de_ambito_pedagogico(): void
    {
        $actaPedagogica = ActaConfidencial::create([
            'titulo' => 'Acta Compromiso Pedagogico UTP',
            'categoria' => 'confidencial',
            'ambito' => 'pedagogico',
            'estudiante_nombre' => 'Alumno Pedagogico',
            'estudiante_run' => '44.444.444-4',
            'apoderado_nombre' => 'Apoderado Pedagogico',
            'fecha' => '2026-10-12',
            'archivo' => 'actas/acta-pedagogica.pdf',
            'user_id' => $this->convivencia->id,
        ]);
        $actaConductual = ActaConfidencial::create([
            'titulo' => 'Acta Confidencial Conductual Privada',
            'categoria' => 'sensible',
            'ambito' => 'conductual',
            'estudiante_nombre' => 'Alumno Conductual',
            'estudiante_run' => '66.666.666-6',
            'apoderado_nombre' => 'Apoderado Conductual',
            'fecha' => '2026-10-13',
            'archivo' => 'actas/acta-conductual.pdf',
            'user_id' => $this->convivencia->id,
        ]);

        Storage::disk('public')->put('actas/acta-pedagogica.pdf', 'contenido pedagogico');
        Storage::disk('public')->put('actas/acta-conductual.pdf', 'contenido conductual');

        $this->actingAs($this->utp)
            ->get(route('actas.index'))
            ->assertOk()
            ->assertSee('Acta Compromiso Pedagogico UTP')
            ->assertDontSee('Acta Confidencial Conductual Privada');

        $this->actingAs($this->utp)
            ->get(route('actas.download', $actaPedagogica))
            ->assertOk();

        $this->actingAs($this->utp)
            ->get(route('actas.download', $actaConductual))
            ->assertForbidden();

        // UTP es solo lectora del módulo
        $this->actingAs($this->utp)->get(route('actas.create'))->assertForbidden();

        // Dirección ve ambos ámbitos
        $this->actingAs($this->directora)
            ->get(route('actas.index'))
            ->assertOk()
            ->assertSee('Acta Confidencial Conductual Privada');
    }

    public function test_busqueda_de_actas_por_run(): void
    {
        ActaConfidencial::create([
            'titulo' => 'Acta Buscable',
            'categoria' => 'confidencial',
            'ambito' => 'pedagogico',
            'estudiante_nombre' => 'Alumno Buscado',
            'estudiante_run' => '77.777.777-7',
            'apoderado_nombre' => 'Apoderado Buscable',
            'fecha' => '2026-10-14',
            'archivo' => 'actas/buscable.pdf',
            'user_id' => $this->convivencia->id,
        ]);
        ActaConfidencial::create([
            'titulo' => 'Acta Otro Estudiante',
            'categoria' => 'confidencial',
            'ambito' => 'pedagogico',
            'estudiante_nombre' => 'Alumno Distinto',
            'estudiante_run' => '88.888.888-8',
            'apoderado_nombre' => 'Apoderado Distinto',
            'fecha' => '2026-10-15',
            'archivo' => 'actas/distinto.pdf',
            'user_id' => $this->convivencia->id,
        ]);

        $this->actingAs($this->convivencia)
            ->get(route('actas.index', ['search' => '77.777.777-7']))
            ->assertOk()
            ->assertSee('Acta Buscable')
            ->assertDontSee('Acta Otro Estudiante');
    }

    // ── HU-06: Reuniones comunales ──

    public function test_utp_carga_reuniones_comunales_y_demas_roles_consultan(): void
    {
        $this->actingAs($this->utp)
            ->post(route('comunales.store'), [
                'titulo' => 'Red UTP — Reunión Mensual',
                'categoria' => 'red_utp',
                'fecha' => '2026-10-20',
                'descripcion' => 'Orientaciones DAEM para el trimestre.',
                'archivo' => UploadedFile::fake()->create('minuta.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $reunion = ReunionComunal::where('titulo', 'Red UTP — Reunión Mensual')->first();
        $this->assertNotNull($reunion);

        foreach ([$this->secretaria, $this->directora, $this->admin, $this->convivencia] as $rol) {
            $this->actingAs($rol)
                ->get(route('comunales.index'))
                ->assertOk()
                ->assertSee('Red UTP — Reunión Mensual');
        }

        // Carga restringida a UTP y Dirección
        $payload = [
            'titulo' => 'Intento Secretaría',
            'categoria' => 'daem_slep',
            'fecha' => '2026-10-21',
            'archivo' => UploadedFile::fake()->create('otra.pdf', 50, 'application/pdf'),
        ];
        $this->actingAs($this->secretaria)->post(route('comunales.store'), $payload)->assertForbidden();
        $this->actingAs($this->admin)->get(route('comunales.create'))->assertForbidden();

        // Todos los roles directivos descargan el documento
        $this->actingAs($this->secretaria)
            ->get(route('comunales.download', $reunion))
            ->assertOk();
    }

    public function test_compromisos_de_reunion_con_fecha_limite(): void
    {
        $reunion = ReunionComunal::create([
            'titulo' => 'Mesa Comunal de Convivencia',
            'categoria' => 'mesa_convivencia',
            'fecha' => '2026-10-22',
            'descripcion' => 'Acuerdos de la mesa.',
            'archivo' => 'reuniones/mesa.pdf',
            'user_id' => $this->utp->id,
        ]);

        // Sin fecha límite: validación impide el registro
        $this->actingAs($this->utp)
            ->from(route('comunales.show', $reunion))
            ->post(route('comunales.compromisos.store', $reunion), [
                'descripcion' => 'Compromiso sin plazo',
            ])
            ->assertSessionHasErrors('fecha_limite');

        $this->assertDatabaseMissing('compromisos_reuniones', ['descripcion' => 'Compromiso sin plazo']);

        $this->actingAs($this->utp)
            ->from(route('comunales.show', $reunion))
            ->post(route('comunales.compromisos.store', $reunion), [
                'descripcion' => 'Enviar plan de mejora a DAEM',
                'fecha_limite' => '2026-11-15',
            ])
            ->assertRedirect(route('comunales.show', $reunion));

        $this->assertDatabaseHas('compromisos_reuniones', [
            'reunion_id' => $reunion->id,
            'descripcion' => 'Enviar plan de mejora a DAEM',
        ]);

        $compromiso = $reunion->compromisos()->first();

        $this->actingAs($this->directora)
            ->get(route('comunales.compromisos.edit', [$reunion, $compromiso]))
            ->assertOk();

        $this->actingAs($this->directora)
            ->put(route('comunales.compromisos.update', [$reunion, $compromiso]), [
                'descripcion' => 'Enviar plan de mejora a DAEM — versión 2',
                'fecha_limite' => '2026-11-20',
            ])
            ->assertRedirect(route('comunales.show', $reunion));

        $this->assertSame('2026-11-20', $compromiso->fresh()->fecha_limite->format('Y-m-d'));

        // Roles sin permiso de gestión no tocan compromisos
        $this->actingAs($this->secretaria)
            ->put(route('comunales.compromisos.update', [$reunion, $compromiso]), [
                'descripcion' => 'Intento bloqueado',
                'fecha_limite' => '2026-12-01',
            ])
            ->assertForbidden();

        $this->actingAs($this->utp)
            ->delete(route('comunales.compromisos.destroy', [$reunion, $compromiso]))
            ->assertRedirect(route('comunales.show', $reunion));

        $this->assertDatabaseMissing('compromisos_reuniones', ['id' => $compromiso->id]);
    }
}
