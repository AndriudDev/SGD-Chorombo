<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Memo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * QA-F4-009: ante una restauración y una purga concurrentes sobre el mismo
 * documento de la papelera, solo una transición incompatible confirma éxito;
 * la otra falla con mensaje controlado, el estado final es coherente y la
 * bitácora conserva un único evento, el de la operación ganadora.
 *
 * La contención se reproduce con dos conexiones MySQL distintas (la por
 * defecto y «papelera_rival», abierta sin transacción de prueba) y una
 * barrera de sincronización: la transacción de la operación ganadora se
 * confirma justo cuando el perdedor inicia la suya —después de releer el
 * documento y antes de revalidarlo con lockForUpdate—, de modo que el
 * perdedor pasa la lectura previa y la revalidación decide el resultado.
 * Se usa DatabaseMigrations (sin transacción envolvente) para que ambas
 * conexiones vean los mismos datos comprometidos, como en la batería QA.
 */
class CorreccionQaF4009ConcurrenciaTest extends TestCase
{
    use DatabaseMigrations;

    private User $directora;

    protected function setUp(): void
    {
        parent::setUp();

        // Segunda conexión al mismo esquema de prueba, fuera de la
        // transacción envolvente (DatabaseMigrations no usa ninguna).
        config([
            'database.connections.papelera_rival' => config('database.connections.mysql'),
        ]);

        $this->directora = $this->userWithRole('directora', 'Dirección');
        $this->actingAs($this->directora);
    }

    protected function tearDown(): void
    {
        // Si el cuerpo del test fallara antes de que la barrera confirme la
        // transacción rival, libera su bloqueo para que el rollback de
        // migraciones no quede esperando un metadata lock indefinidamente.
        if ($this->app?->bound('db') && config('database.connections.papelera_rival')) {
            $rival = DB::connection('papelera_rival');

            if ($rival->transactionLevel() > 0) {
                $rival->rollBack();
            }
        }

        parent::tearDown();
    }

    public function test_purga_concurrente_falla_controladamente_cuando_la_restauracion_gana(): void
    {
        $memo = $this->crearMemoEnPapelera();

        // La «otra pestaña» restaura primero y retiene el bloqueo de fila
        // sin confirmar su transacción.
        $rival = DB::connection('papelera_rival');
        $rival->beginTransaction();
        Memo::on('papelera_rival')->withTrashed()->findOrFail($memo->id)->restore();

        $this->confirmarRivalAlIniciarElPerdedor($rival);

        $this->delete(route('papelera.destroy', ['memos', $memo->id]))
            ->assertRedirect()
            ->assertSessionHas('error')
            ->assertSessionMissing('success');

        $this->assertSame(0, $rival->transactionLevel(), 'La barrera no confirmó la transacción ganadora.');

        // Estado coherente: el documento quedó restaurado, no purgado.
        $this->assertFalse($memo->fresh()->trashed());

        // Un único evento: el de la operación ganadora (restauración).
        $this->assertEqualsCanonicalizing(
            [Auditoria::ACCION_CREAR, Auditoria::ACCION_ELIMINAR, Auditoria::ACCION_RESTAURAR],
            $this->trazasDe($memo),
        );
    }

    public function test_restauracion_concurrente_falla_controladamente_cuando_la_purga_gana(): void
    {
        Storage::fake('public');

        $ruta = 'memos/qa-f4-009.pdf';
        Storage::disk('public')->put($ruta, 'contenido de prueba');

        $memo = $this->crearMemoEnPapelera(['archivo' => $ruta]);

        // La «otra pestaña» purga primero (registro fuera y archivo borrado)
        // y retiene el bloqueo de fila sin confirmar su transacción.
        $rival = DB::connection('papelera_rival');
        $rival->beginTransaction();
        Memo::on('papelera_rival')->withTrashed()->findOrFail($memo->id)->forceDelete();
        Storage::disk('public')->delete($ruta);

        $this->confirmarRivalAlIniciarElPerdedor($rival);

        $this->post(route('papelera.restaurar', ['memos', $memo->id]))
            ->assertRedirect()
            ->assertSessionHas('error')
            ->assertSessionMissing('success');

        $this->assertSame(0, $rival->transactionLevel(), 'La barrera no confirmó la transacción ganadora.');

        // Estado coherente: el documento quedó purgado con su archivo.
        $this->assertDatabaseMissing('memos', ['id' => $memo->id]);
        Storage::disk('public')->assertMissing($ruta);

        // Un único evento: el de la operación ganadora (purga definitiva).
        $this->assertEqualsCanonicalizing(
            [Auditoria::ACCION_CREAR, Auditoria::ACCION_ELIMINAR, Auditoria::ACCION_ELIMINAR_DEFINITIVO],
            $this->trazasDe($memo),
        );
    }

    /**
     * Barrera de sincronización (QA-F4-009): al iniciarse la transacción de
     * la operación perdedora —sobre la conexión por defecto, ya pasada la
     * lectura previa del documento— se confirma la transacción de la
     * ganadora, que hasta entonces retenía el bloqueo de fila.
     */
    private function confirmarRivalAlIniciarElPerdedor(Connection $rival): void
    {
        $pendiente = true;

        Event::listen(TransactionBeginning::class, function (TransactionBeginning $evento) use (&$pendiente, $rival) {
            if (! $pendiente || $evento->connection->getName() !== config('database.default')) {
                return;
            }

            $pendiente = false;
            $rival->commit();
        });
    }

    private function crearMemoEnPapelera(array $extra = []): Memo
    {
        $memo = Memo::create(array_merge([
            'folio' => 'MEMO-2026-049',
            'tipo' => 'memo',
            'fecha' => Carbon::parse('2026-11-20'),
            'destinatario' => 'Dirección Escolar',
            'asunto' => 'Asunto QA-F4-009',
            'user_id' => $this->directora->id,
        ], $extra));

        $memo->delete();

        return $memo;
    }

    /**
     * @return array<int, string> acciones de la bitácora para el documento
     */
    private function trazasDe(Memo $memo): array
    {
        return Auditoria::query()
            ->where('modelo_type', $memo->getMorphClass())
            ->where('modelo_id', $memo->id)
            ->pluck('accion')
            ->all();
    }

    private function userWithRole(string $roleName, string $displayName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => $displayName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
