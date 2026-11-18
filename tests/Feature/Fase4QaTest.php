<?php

namespace Tests\Feature;

use App\Models\ActaConfidencial;
use App\Models\Auditoria;
use App\Models\Citacion;
use App\Models\Memo;
use App\Models\PermisoAdministrativo;
use App\Models\ReunionComunal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Fase4QaTest extends TestCase
{
    use RefreshDatabase;

    private User $directora;

    private User $inspectoria;

    private User $admin;

    private User $secretaria;

    private User $utp;

    private User $convivencia;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->directora = $this->crearUsuarioConRol('directora', 'Dirección');
        $this->inspectoria = $this->crearUsuarioConRol('inspectoria', 'Inspectoría General');
        $this->admin = $this->crearUsuarioConRol('administracion', 'Administración');
        $this->secretaria = $this->crearUsuarioConRol('secretaria', 'Secretaría');
        $this->utp = $this->crearUsuarioConRol('utp', 'Coordinación Académica');
        $this->convivencia = $this->crearUsuarioConRol('convivencia', 'Convivencia Escolar');
    }

    private function crearUsuarioConRol(string $rol, string $display): User
    {
        $role = Role::create(['name' => $rol, 'display_name' => $display]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    private function crearMemo(array $atributos = []): Memo
    {
        return Memo::create(array_merge([
            'folio' => 'MEMO-2026-0001',
            'tipo' => 'memo',
            'fecha' => '2026-11-01',
            'destinatario' => 'Dirección',
            'asunto' => 'Asunto de prueba Fase 4',
            'user_id' => $this->secretaria->id,
        ], $atributos));
    }

    private function crearCitacion(array $atributos = []): Citacion
    {
        return Citacion::create(array_merge([
            'estudiante_nombre' => 'Alumno Fase4',
            'estudiante_run' => '15.151.151-5',
            'apoderado_nombre' => 'Madre del Alumno Fase4',
            'funcionario_cita' => 'Convivencia Escolar',
            'fecha' => '2026-11-05',
            'hora' => '10:00',
            'motivo' => 'conductual',
            'estado' => 'programada',
            'user_id' => $this->convivencia->id,
        ], $atributos));
    }

    private function crearActa(array $atributos = []): ActaConfidencial
    {
        return ActaConfidencial::create(array_merge([
            'titulo' => 'Acta Acuerdo Fase4',
            'categoria' => 'confidencial',
            'ambito' => 'conductual',
            'estudiante_nombre' => 'Alumno Reservado',
            'estudiante_run' => '16.161.161-6',
            'apoderado_nombre' => 'Madre del Alumno Reservado',
            'fecha' => '2026-11-03',
            'user_id' => $this->directora->id,
        ], $atributos));
    }

    private function crearReunion(array $atributos = []): ReunionComunal
    {
        return ReunionComunal::create(array_merge([
            'titulo' => 'Reunión Comunal Fase4',
            'categoria' => 'red_utp',
            'fecha' => '2026-11-04',
            'descripcion' => 'Reunión de prueba de la fase 4',
            'user_id' => $this->utp->id,
        ], $atributos));
    }

    private function crearPermiso(array $atributos = []): PermisoAdministrativo
    {
        return PermisoAdministrativo::create(array_merge([
            'user_id' => $this->secretaria->id,
            'fecha_inicio' => '2026-11-10',
            'fecha_fin' => '2026-11-11',
            'tipo_jornada' => 'completa',
            'motivo' => 'Trámite personal Fase4',
            'estado' => 'pendiente',
            'created_by' => $this->secretaria->id,
        ], $atributos));
    }

    // ── HU-07: Búsqueda y filtros combinables ──

    public function test_buscador_combina_texto_rango_fecha_tipo_y_emisor(): void
    {
        $this->crearMemo([
            'folio' => 'MEMO-2026-0001',
            'fecha' => '2026-11-01',
            'asunto' => 'Compra de computadores para el laboratorio',
            'user_id' => $this->secretaria->id,
        ]);
        $this->crearMemo([
            'folio' => 'MEMO-2026-0002',
            'fecha' => '2026-11-10',
            'asunto' => 'Oficio sobre la red de directores',
            'user_id' => $this->directora->id,
        ]);

        $this->actingAs($this->utp)
            ->get(route('buscador.index', [
                'q' => 'computadores',
                'fecha_desde' => '2026-11-01',
                'fecha_hasta' => '2026-11-05',
                'tipo' => 'memo',
                'emisor' => $this->secretaria->id,
            ]))
            ->assertOk()
            ->assertSee('MEMO-2026-0001')
            ->assertDontSee('MEMO-2026-0002');

        // La combinación restringe: fuera del rango no hay coincidencias.
        $this->actingAs($this->utp)
            ->get(route('buscador.index', ['q' => 'computadores', 'fecha_desde' => '2026-11-20']))
            ->assertOk()
            ->assertSee('No hay coincidencias')
            ->assertDontSee('MEMO-2026-0001');
    }

    public function test_buscador_no_revela_reservados_ni_modulos_restringidos(): void
    {
        $this->crearActa();
        $this->crearCitacion();

        // Los perfiles autorizados del HU-05 encuentran el acta.
        $this->actingAs($this->directora)
            ->get(route('buscador.index', ['q' => 'Acuerdo Fase4']))
            ->assertOk()
            ->assertSee('Acta Acuerdo Fase4');
        $this->actingAs($this->convivencia)
            ->get(route('buscador.index', ['q' => 'Acuerdo Fase4']))
            ->assertOk()
            ->assertSee('Acta Acuerdo Fase4');

        // Ningún otro perfil la revela, ni pidiendo el tipo explícito.
        foreach ([$this->utp, $this->secretaria, $this->inspectoria, $this->admin] as $perfil) {
            $this->actingAs($perfil)
                ->get(route('buscador.index', ['q' => 'Acuerdo Fase4']))
                ->assertOk()
                ->assertDontSee('Acta Acuerdo Fase4');
            $this->actingAs($perfil)
                ->get(route('buscador.index', ['tipo' => 'acta']))
                ->assertOk()
                ->assertDontSee('Acta Acuerdo Fase4');
        }

        // Inspectoría no lee citaciones (matriz de lectura HU-04): tampoco en la búsqueda.
        // (El texto de la consulta se refleja en la caja, así que se afirma sobre la fila:
        // el apoderado solo aparece en los resultados de la citación.)
        $this->actingAs($this->inspectoria)
            ->get(route('buscador.index', ['q' => 'Alumno Fase4']))
            ->assertOk()
            ->assertDontSee('Madre del Alumno Fase4');
        $this->actingAs($this->secretaria)
            ->get(route('buscador.index', ['q' => 'Alumno Fase4']))
            ->assertOk()
            ->assertSee('Madre del Alumno Fase4');
    }

    public function test_buscador_filtra_por_estado(): void
    {
        $this->crearPermiso();
        $this->crearCitacion();

        $this->actingAs($this->directora)
            ->get(route('buscador.index', ['estado' => 'pendiente']))
            ->assertOk()
            ->assertSee('Trámite personal Fase4')
            ->assertDontSee('Alumno Fase4');

        $this->actingAs($this->directora)
            ->get(route('buscador.index', ['estado' => 'programada']))
            ->assertOk()
            ->assertSee('Alumno Fase4')
            ->assertDontSee('Trámite personal Fase4');

        $this->actingAs($this->directora)
            ->get(route('buscador.index', ['estado' => 'aprobado']))
            ->assertOk()
            ->assertDontSee('Trámite personal Fase4')
            ->assertDontSee('Alumno Fase4');
    }

    public function test_buscador_respeta_visibilidad_fila_a_fila_de_permisos(): void
    {
        // Permiso registrado por UTP: Secretaría no es supervisora ni titular.
        $this->crearPermiso(['user_id' => $this->utp->id, 'created_by' => $this->utp->id]);

        // Se busca por un término corto: la frase completa solo puede provenir de la fila de resultados.
        $this->actingAs($this->secretaria)
            ->get(route('buscador.index', ['q' => 'Trámite']))
            ->assertOk()
            ->assertDontSee('Trámite personal Fase4');

        $this->actingAs($this->inspectoria)
            ->get(route('buscador.index', ['q' => 'Trámite']))
            ->assertOk()
            ->assertSee('Trámite personal Fase4');
    }

    // ── HU-08: Eliminación restringida ──

    public function test_eliminacion_restringida_en_todos_los_modulos(): void
    {
        $memo = $this->crearMemo();
        $citacion = $this->crearCitacion();
        $reunion = $this->crearReunion();
        $acta = $this->crearActa();

        // Perfiles fuera del set HU-08: 403 y ningún registro tocado.
        $this->actingAs($this->secretaria)->delete(route('memos.destroy', $memo))->assertForbidden();
        $this->actingAs($this->utp)->delete(route('memos.destroy', $memo))->assertForbidden();
        $this->actingAs($this->secretaria)->delete(route('citaciones.destroy', $citacion))->assertForbidden();
        $this->actingAs($this->convivencia)->delete(route('citaciones.destroy', $citacion))->assertForbidden();
        $this->actingAs($this->utp)->delete(route('comunales.destroy', $reunion))->assertForbidden();
        $this->actingAs($this->convivencia)->delete(route('actas.destroy', $acta))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('actas.destroy', $acta))->assertForbidden();

        $this->assertNull($memo->fresh()->deleted_at);
        $this->assertNull($citacion->fresh()->deleted_at);
        $this->assertNull($reunion->fresh()->deleted_at);
        $this->assertNull($acta->fresh()->deleted_at);

        // Perfiles autorizados por HU-08 → papelera (borrado lógico).
        $this->actingAs($this->inspectoria)
            ->delete(route('memos.destroy', $memo))
            ->assertRedirect(route('memos.index'));
        $this->assertSoftDeleted('memos', ['id' => $memo->id]);

        $this->actingAs($this->admin)
            ->delete(route('citaciones.destroy', $citacion))
            ->assertRedirect(route('citaciones.index'));
        $this->assertSoftDeleted('citaciones', ['id' => $citacion->id]);

        $this->actingAs($this->directora)
            ->delete(route('comunales.destroy', $reunion))
            ->assertRedirect(route('comunales.index'));
        $this->assertSoftDeleted('reuniones_comunales', ['id' => $reunion->id]);

        $this->actingAs($this->directora)
            ->delete(route('actas.destroy', $acta))
            ->assertRedirect(route('actas.index'));
        $this->assertSoftDeleted('actas_confidenciales', ['id' => $acta->id]);

        // Un documento en papelera deja de aparecer como activo.
        $this->actingAs($this->directora)
            ->get(route('memos.index'))
            ->assertDontSee($memo->folio);
    }

    // ── HU-08: Papelera y recuperación ──

    public function test_papelera_recupera_documento_con_su_archivo(): void
    {
        Storage::disk('public')->put('memos/memo-fase4.pdf', 'contenido');
        $memo = $this->crearMemo(['archivo' => 'memos/memo-fase4.pdf']);

        $this->actingAs($this->directora)->delete(route('memos.destroy', $memo));
        $this->assertNull(Memo::find($memo->id));
        $this->assertTrue(Storage::disk('public')->exists('memos/memo-fase4.pdf'), 'El archivo debe conservarse en papelera.');

        $this->actingAs($this->directora)
            ->get(route('papelera.index'))
            ->assertOk()
            ->assertSee($memo->folio);

        $this->actingAs($this->directora)
            ->from(route('papelera.index'))
            ->post(route('papelera.restaurar', ['modulo' => 'memos', 'id' => $memo->id]))
            ->assertRedirect(route('papelera.index'));

        $this->assertNull($memo->fresh()->deleted_at);
        $this->assertTrue(Storage::disk('public')->exists('memos/memo-fase4.pdf'));
        $this->actingAs($this->directora)
            ->get(route('memos.index'))
            ->assertSee($memo->folio);
    }

    public function test_papelera_respeta_la_lectura_de_cada_modulo(): void
    {
        $citacion = $this->crearCitacion();
        $acta = $this->crearActa();

        $this->actingAs($this->directora)->delete(route('citaciones.destroy', $citacion));
        $this->actingAs($this->directora)->delete(route('actas.destroy', $acta));

        // La ruta de papelera pertenece al set HU-08.
        $this->actingAs($this->secretaria)->get(route('papelera.index'))->assertForbidden();
        $this->actingAs($this->utp)->get(route('papelera.index'))->assertForbidden();
        $this->actingAs($this->convivencia)->get(route('papelera.index'))->assertForbidden();

        // Inspectoría no ve módulos que no lee (HU-04/HU-05).
        $this->actingAs($this->inspectoria)
            ->get(route('papelera.index'))
            ->assertOk()
            ->assertDontSee($citacion->estudiante_nombre)
            ->assertDontSee($acta->titulo);

        // Administración ve citaciones pero no reservados (HU-05).
        $this->actingAs($this->admin)
            ->get(route('papelera.index'))
            ->assertOk()
            ->assertSee($citacion->estudiante_nombre)
            ->assertDontSee($acta->titulo);

        // Dirección ve ambas secciones.
        $this->actingAs($this->directora)
            ->get(route('papelera.index'))
            ->assertOk()
            ->assertSee($citacion->estudiante_nombre)
            ->assertSee($acta->titulo);

        // Sin lectura del módulo no hay restauración ni purga.
        $this->actingAs($this->inspectoria)
            ->from(route('papelera.index'))
            ->post(route('papelera.restaurar', ['modulo' => 'citaciones', 'id' => $citacion->id]))
            ->assertForbidden();
        $this->assertNotNull($citacion->fresh()->deleted_at);

        $this->actingAs($this->admin)
            ->from(route('papelera.index'))
            ->delete(route('papelera.destroy', ['modulo' => 'actas', 'id' => $acta->id]))
            ->assertForbidden();
        $this->assertNotNull($acta->fresh()->deleted_at);
    }

    public function test_eliminacion_definitiva_borra_archivo_y_registra_bitacora(): void
    {
        Storage::disk('public')->put('reuniones/reunion-fase4.pdf', 'contenido');
        $reunion = $this->crearReunion(['archivo' => 'reuniones/reunion-fase4.pdf']);

        $this->actingAs($this->directora)->delete(route('comunales.destroy', $reunion));

        $this->actingAs($this->directora)
            ->from(route('papelera.index'))
            ->delete(route('papelera.destroy', ['modulo' => 'reuniones', 'id' => $reunion->id]))
            ->assertRedirect(route('papelera.index'));

        $this->assertDatabaseMissing('reuniones_comunales', ['id' => $reunion->id]);
        $this->assertFalse(Storage::disk('public')->exists('reuniones/reunion-fase4.pdf'));

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'eliminar_definitivo',
            'modelo_type' => ReunionComunal::class,
            'modelo_id' => $reunion->id,
            'user_id' => $this->directora->id,
        ]);
        // La purga no duplica la fila de eliminación lógica previa.
        $this->assertSame(1, Auditoria::where('modelo_id', $reunion->id)->where('accion', 'eliminar')->count());
        $this->assertSame(1, Auditoria::where('modelo_id', $reunion->id)->where('accion', 'eliminar_definitivo')->count());
    }

    // ── HU-08: Bitácora de auditoría ──

    public function test_bitacora_registra_carga_edicion_descarga_eliminacion_y_restauracion(): void
    {
        Storage::disk('public')->put('memos/bitacora-fase4.pdf', 'contenido');

        // Carga vía controlador.
        $this->actingAs($this->secretaria)
            ->from(route('memos.index'))
            ->post(route('memos.store'), [
                'tipo' => 'memo',
                'fecha' => '2026-11-20',
                'destinatario' => 'Inspectoría General',
                'asunto' => 'Documento para bitácora',
            ])
            ->assertRedirect(route('memos.index'));

        $memo = Memo::where('asunto', 'Documento para bitácora')->firstOrFail();

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'crear',
            'modelo_type' => Memo::class,
            'modelo_id' => $memo->id,
            'modelo_nombre' => $memo->folio,
            'user_id' => $this->secretaria->id,
        ]);

        // Edición con captura de cambios anterior → nuevo.
        $this->actingAs($this->secretaria);
        $memo->update(['asunto' => 'Documento modificado']);

        $edicion = Auditoria::where('accion', 'editar')->where('modelo_id', $memo->id)->first();
        $this->assertNotNull($edicion);
        $this->assertSame($this->secretaria->id, $edicion->user_id);
        $this->assertSame('Documento para bitácora', $edicion->detalles['asunto'][0]);
        $this->assertSame('Documento modificado', $edicion->detalles['asunto'][1]);

        // Guardar solo el adjunto no es una edición del registro.
        $memo->archivo = 'memos/bitacora-fase4.pdf';
        $memo->save();
        $this->assertSame(1, Auditoria::where('accion', 'editar')->where('modelo_id', $memo->id)->count());

        // Descarga.
        $this->actingAs($this->directora)
            ->get(route('memos.download', $memo))
            ->assertDownload('bitacora-fase4.pdf');
        $this->assertDatabaseHas('auditorias', [
            'accion' => 'descargar',
            'modelo_type' => Memo::class,
            'modelo_id' => $memo->id,
            'user_id' => $this->directora->id,
        ]);

        // Eliminación a papelera y restauración.
        $this->actingAs($this->directora)
            ->delete(route('memos.destroy', $memo))
            ->assertRedirect(route('memos.index'));
        $this->assertDatabaseHas('auditorias', [
            'accion' => 'eliminar',
            'modelo_id' => $memo->id,
            'user_id' => $this->directora->id,
        ]);

        $this->actingAs($this->directora)
            ->from(route('papelera.index'))
            ->post(route('papelera.restaurar', ['modulo' => 'memos', 'id' => $memo->id]));
        $this->assertDatabaseHas('auditorias', [
            'accion' => 'restaurar',
            'modelo_id' => $memo->id,
            'user_id' => $this->directora->id,
        ]);
    }

    public function test_bitacora_filtra_por_lectura_de_modulos(): void
    {
        $memo = $this->crearMemo();
        $this->crearCitacion();
        $this->crearActa();

        // La bitácora pertenece al set HU-08.
        $this->actingAs($this->secretaria)->get(route('auditoria.index'))->assertForbidden();
        $this->actingAs($this->utp)->get(route('auditoria.index'))->assertForbidden();

        // Inspectoría ve memos/permisos pero no citaciones ni actas.
        $this->actingAs($this->inspectoria)
            ->get(route('auditoria.index'))
            ->assertOk()
            ->assertSee($memo->folio)
            ->assertDontSee('Citación: Alumno Fase4')
            ->assertDontSee('Acta: Acta Acuerdo Fase4');

        // Administración ve citaciones pero no reservados (HU-05).
        $this->actingAs($this->admin)
            ->get(route('auditoria.index'))
            ->assertOk()
            ->assertSee('Citación: Alumno Fase4')
            ->assertDontSee('Acta: Acta Acuerdo Fase4');

        // Dirección ve todo.
        $this->actingAs($this->directora)
            ->get(route('auditoria.index'))
            ->assertOk()
            ->assertSee('Citación: Alumno Fase4')
            ->assertSee('Acta: Acta Acuerdo Fase4');
    }

    // ── HU-02 + HU-08: el folio de un documento en papelera no se reutiliza ──

    public function test_folio_no_se_reutiliza_con_documentos_en_papelera(): void
    {
        $memo = $this->crearMemo(['folio' => 'MEMO-2026-0001', 'fecha' => '2026-01-10']);

        $this->actingAs($this->directora)->delete(route('memos.destroy', $memo));

        $this->actingAs($this->secretaria)
            ->from(route('memos.index'))
            ->post(route('memos.store'), [
                'tipo' => 'memo',
                'fecha' => '2026-01-20',
                'destinatario' => 'DAEM',
                'asunto' => 'Nuevo memo tras enviar a papelera',
            ])
            ->assertRedirect(route('memos.index'));

        $nuevo = Memo::where('asunto', 'Nuevo memo tras enviar a papelera')->firstOrFail();

        $this->assertSame(
            'MEMO-2026-0002',
            $nuevo->folio,
            'El folio de un memo en papelera no debe reutilizarse (evitaría restaurarlo).'
        );
    }
}
