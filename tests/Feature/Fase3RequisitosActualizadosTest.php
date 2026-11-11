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

class Fase3RequisitosActualizadosTest extends TestCase
{
    use RefreshDatabase;

    private User $convivencia;

    private User $secretaria;

    private User $directora;

    private User $utp;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->convivencia = $this->crearUsuarioConRol('convivencia', 'Convivencia');
        $this->secretaria = $this->crearUsuarioConRol('secretaria', 'Secretaria');
        $this->directora = $this->crearUsuarioConRol('directora', 'Directora');
        $this->utp = $this->crearUsuarioConRol('utp', 'UTP');
    }

    private function crearUsuarioConRol(string $rol, string $display): User
    {
        $role = Role::create(['name' => $rol, 'display_name' => $display]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    // ── HU-05: UTP sin acceso a confidenciales/reservados ──

    public function test_utp_no_encuentra_ni_descarga_documentos_reservados_ni_en_ambito_pedagogico(): void
    {
        $acta = ActaConfidencial::create([
            'titulo' => 'Acta Reservada Pedagogica',
            'categoria' => 'confidencial',
            'ambito' => 'pedagogico',
            'estudiante_nombre' => 'Alumno Reservado',
            'estudiante_run' => '99.999.999-9',
            'apoderado_nombre' => 'Apoderado Reservado',
            'fecha' => '2026-11-02',
            'archivo' => 'actas/reservada.pdf',
            'user_id' => $this->convivencia->id,
        ]);

        Storage::disk('local')->put('actas/reservada.pdf', 'contenido reservado');

        $this->actingAs($this->utp)->get(route('actas.index'))->assertForbidden();
        $this->actingAs($this->utp)->get(route('actas.download', $acta))->assertForbidden();
        $this->actingAs($this->utp)->get(route('actas.create'))->assertForbidden();
        $this->actingAs($this->utp)->delete(route('actas.destroy', $acta))->assertForbidden();

        // La búsqueda tampoco revela el documento
        $this->actingAs($this->utp)
            ->get(route('actas.index', ['search' => '99.999.999-9']))
            ->assertForbidden();
    }

    // ── HU-05: sin enlaces públicos que eviten la autorización ──

    public function test_archivo_reservado_se_almacena_en_disco_privado(): void
    {
        $this->actingAs($this->convivencia)
            ->post(route('actas.store'), [
                'titulo' => 'Acta Sin Enlace Publico',
                'categoria' => 'confidencial',
                'ambito' => 'conductual',
                'estudiante_nombre' => 'Alumno Privado',
                'estudiante_run' => '10.000.000-0',
                'apoderado_nombre' => 'Apoderado Privado',
                'fecha' => '2026-11-03',
                'archivo' => UploadedFile::fake()->create('acta.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('actas.index'));

        $acta = ActaConfidencial::where('estudiante_run', '10.000.000-0')->first();
        $this->assertNotNull($acta);

        Storage::disk('local')->assertExists($acta->archivo);
        Storage::disk('public')->assertMissing($acta->archivo);

        $this->actingAs($this->directora)
            ->get(route('actas.download', $acta))
            ->assertOk();
    }

    // ── HU-05: numeración de reserva asignada por Dirección ──

    public function test_direccion_asigna_y_consulta_la_numeracion_reservada(): void
    {
        // Solo Dirección ve el campo de numeración en el formulario
        $this->actingAs($this->directora)
            ->get(route('actas.create'))
            ->assertOk()
            ->assertSee('numero_reserva');

        $this->actingAs($this->convivencia)
            ->get(route('actas.create'))
            ->assertOk()
            ->assertDontSee('numero_reserva');

        // Convivencia registra el acta: la numeración que intente enviar no se persiste
        $this->actingAs($this->convivencia)
            ->post(route('actas.store'), [
                'titulo' => 'Acta Numerada',
                'categoria' => 'sensible',
                'ambito' => 'conductual',
                'estudiante_nombre' => 'Alumno Numerado',
                'estudiante_run' => '12.345.678-9',
                'apoderado_nombre' => 'Apoderado Numerado',
                'fecha' => '2026-11-04',
                'numero_reserva' => 'INTENTO-NO-AUTORIZADO',
                'archivo' => UploadedFile::fake()->create('acta.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('actas.index'));

        $acta = ActaConfidencial::where('estudiante_run', '12.345.678-9')->first();
        $this->assertNotNull($acta);
        $this->assertNull($acta->numero_reserva);

        // Dirección asigna la numeración al editar
        $this->actingAs($this->directora)
            ->put(route('actas.update', $acta), [
                'titulo' => 'Acta Numerada',
                'categoria' => 'sensible',
                'ambito' => 'conductual',
                'estudiante_nombre' => 'Alumno Numerado',
                'estudiante_run' => '12.345.678-9',
                'apoderado_nombre' => 'Apoderado Numerado',
                'fecha' => '2026-11-04',
                'numero_reserva' => 'RES-2026-001',
            ])
            ->assertRedirect(route('actas.index'));

        $this->assertSame('RES-2026-001', $acta->fresh()->numero_reserva);

        // Dirección consulta la numeración en el listado
        $this->actingAs($this->directora)
            ->get(route('actas.index'))
            ->assertOk()
            ->assertSee('RES-2026-001');

        // Convivencia edita sin alterar la numeración ya asignada
        $this->actingAs($this->convivencia)
            ->put(route('actas.update', $acta), [
                'titulo' => 'Acta Numerada Editada',
                'categoria' => 'sensible',
                'ambito' => 'conductual',
                'estudiante_nombre' => 'Alumno Numerado',
                'estudiante_run' => '12.345.678-9',
                'apoderado_nombre' => 'Apoderado Numerado',
                'fecha' => '2026-11-04',
            ])
            ->assertRedirect(route('actas.index'));

        $this->assertSame('RES-2026-001', $acta->fresh()->numero_reserva);
    }

    // ── HU-06: fotografía de acta manuscrita ──

    public function test_directora_carga_fotografia_de_acta_manuscrita_y_la_recupera(): void
    {
        $this->actingAs($this->directora)
            ->post(route('comunales.store'), [
                'titulo' => 'Mesa Comunal — acta manuscrita',
                'categoria' => 'mesa_convivencia',
                'fecha' => '2026-11-05',
                'descripcion' => 'Acta escrita a mano, sin transcribir.',
                'archivo' => UploadedFile::fake()->create('acta-manuscrita.jpg', 200, 'image/jpeg'),
            ])
            ->assertRedirect();

        $reunion = ReunionComunal::where('titulo', 'Mesa Comunal — acta manuscrita')->first();
        $this->assertNotNull($reunion);
        Storage::disk('public')->assertExists($reunion->archivo);

        $this->actingAs($this->directora)
            ->get(route('comunales.download', $reunion))
            ->assertOk();

        // El formulario admite la fotografía del acta manuscrita
        $this->actingAs($this->directora)
            ->get(route('comunales.create'))
            ->assertOk()
            ->assertSee('fotografía del acta manuscrita');

        // Recuperable también por quienes pueden consultar el módulo
        $this->actingAs($this->secretaria)
            ->get(route('comunales.index', ['search' => 'acta manuscrita']))
            ->assertOk()
            ->assertSee('Mesa Comunal — acta manuscrita');
    }

    public function test_reunion_se_registra_sin_archivo_cuando_no_existe_acta(): void
    {
        $this->actingAs($this->utp)
            ->post(route('comunales.store'), [
                'titulo' => 'Red UTP — sesión sin documento',
                'categoria' => 'red_utp',
                'fecha' => '2026-11-06',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reuniones_comunales', [
            'titulo' => 'Red UTP — sesión sin documento',
            'archivo' => null,
        ]);
    }

    public function test_reunion_se_localiza_por_fecha(): void
    {
        ReunionComunal::create([
            'titulo' => 'Reunión del 6 de noviembre',
            'categoria' => 'reunion_directores',
            'fecha' => '2026-11-06',
            'archivo' => 'reuniones/un-dia.pdf',
            'user_id' => $this->utp->id,
        ]);
        ReunionComunal::create([
            'titulo' => 'Reunión del 7 de noviembre',
            'categoria' => 'reunion_directores',
            'fecha' => '2026-11-07',
            'archivo' => 'reuniones/otro-dia.pdf',
            'user_id' => $this->utp->id,
        ]);

        $this->actingAs($this->directora)
            ->get(route('comunales.index', ['fecha' => '2026-11-06']))
            ->assertOk()
            ->assertSee('Reunión del 6 de noviembre')
            ->assertDontSee('Reunión del 7 de noviembre');
    }

    // ── HU-04: respaldo fotográfico de la citación ──

    public function test_citacion_admite_fotografia_del_documento_como_respaldo(): void
    {
        $this->actingAs($this->secretaria)
            ->post(route('citaciones.store'), [
                'estudiante_nombre' => 'Alumno Foto',
                'estudiante_run' => '13.243.546-5',
                'apoderado_nombre' => 'Apoderado Foto',
                'funcionario_cita' => 'Secretaría',
                'fecha' => '2026-11-08',
                'hora' => '11:30',
                'motivo' => 'conductual',
                'archivo' => UploadedFile::fake()->create('colilla.jpg', 150, 'image/jpeg'),
            ])
            ->assertRedirect(route('citaciones.index'));

        $citacion = Citacion::where('estudiante_run', '13.243.546-5')->first();
        $this->assertNotNull($citacion->archivo);
        Storage::disk('public')->assertExists($citacion->archivo);

        $this->actingAs($this->convivencia)
            ->get(route('citaciones.download', $citacion))
            ->assertOk();
    }
}
