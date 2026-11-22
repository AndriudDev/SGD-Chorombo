<?php

namespace App\Http\Controllers\Auditoria;

use App\Http\Controllers\Controller;
use App\Models\PermisoAdministrativo;
use App\Models\User;
use App\Services\DocumentosPermitidos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class PapeleraController extends Controller
{
    /** Documentos en papelera por módulo (HU-08). */
    private const POR_PAGINA = 50;

    /**
     * List trashed documents per module, limited to modules the user may
     * read and delete (HU-08: recuperación autorizada).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $secciones = [];

        foreach (DocumentosPermitidos::MODULOS as $modulo => $clase) {
            if (! DocumentosPermitidos::puedeEliminar($user, $clase)) {
                continue;
            }

            // Paginación independiente por módulo: cada sección usa su propio
            // parámetro de página (page_<modulo>) para no colisionar entre
            // secciones y permitir llegar más allá de los 50 documentos
            // (QA-F4-008).
            $pageName = "page_{$modulo}";
            $pagina = max(1, (int) $request->input($pageName, 1));

            $query = $clase::query()
                ->onlyTrashed()
                ->with($clase === PermisoAdministrativo::class ? 'creador' : 'user')
                ->orderBy('deleted_at', 'desc');

            $total = $query->count();

            if ($total === 0) {
                continue;
            }

            // Acotar a la última página real: una página pedida fuera de
            // rango no deja la sección vacía ni con paginación incoherente.
            $pagina = min($pagina, max(1, (int) ceil($total / self::POR_PAGINA)));

            $secciones[$modulo] = new LengthAwarePaginator(
                $query->forPage($pagina, self::POR_PAGINA)->get(),
                $total,
                self::POR_PAGINA,
                $pagina,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                    'pageName' => $pageName,
                ]
            );
        }

        return view('papelera.index', [
            'secciones' => $secciones,
            'etiquetas' => DocumentosPermitidos::ETIQUETAS,
        ]);
    }

    /**
     * Restore a document from the papelera (HU-08).
     */
    public function restaurar(Request $request, string $modulo, int $id): RedirectResponse
    {
        $documento = $this->trashed($request, $modulo, $id);

        try {
            // QA-F4-005: la traza de auditoría es parte de la misma transacción;
            // si no puede registrarse, la restauración no se aplica.
            // QA-F4-009: dentro de la transacción se relee el documento con
            // bloqueo de fila y se revalida que siga en papelera; si la purga
            // concurrente ya ganó, no se aplica nada.
            $aplicado = DB::transaction(function () use ($documento) {
                $vigente = $this->releerEnPapelera($documento);

                if ($vigente === null) {
                    return false;
                }

                $vigente->restore();

                return true;
            });
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'No se pudo registrar la restauración en la bitácora. El documento se conserva en la papelera.');
        }

        if (! $aplicado) {
            return back()->with('error', 'El documento ya no está en la papelera: la operación no se aplicó.');
        }

        return back()->with('success', "«{$documento->etiquetaAuditoria()}» restaurado correctamente.");
    }

    /**
     * Permanently delete a document from the papelera, including its file (HU-08).
     */
    public function destroy(Request $request, string $modulo, int $id): RedirectResponse
    {
        $documento = $this->trashed($request, $modulo, $id);

        try {
            // QA-F4-005: la purga irreversible exige traza; sin auditoría no se purga.
            // QA-F4-002/003: el adjunto se resuelve en la misma transacción, después
            // del forceDelete; un fallo de BD o un archivo no borrable revierten la
            // purga y conservan el documento (y su archivo) en la papelera.
            // QA-F4-009: dentro de la transacción se relee el documento con bloqueo
            // de fila y se revalida que siga en papelera; si la restauración
            // concurrente ya ganó, no se purga nada.
            $aplicado = DB::transaction(function () use ($documento) {
                $vigente = $this->releerEnPapelera($documento);

                if ($vigente === null) {
                    return false;
                }

                $vigente->forceDelete();

                if ($vigente->archivo) {
                    $this->borrarAdjuntos($vigente);
                }

                return true;
            });
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'No se pudo completar la eliminación definitiva. El documento y su archivo se conservan en la papelera.');
        }

        if (! $aplicado) {
            return back()->with('error', 'El documento ya no está en la papelera: la operación no se aplicó.');
        }

        return back()->with('success', "«{$documento->etiquetaAuditoria()}» eliminado definitivamente.");
    }

    /**
     * Elimina el adjunto de todos los discos en los que exista: el declarado
     * por el modelo y las copias legadas en public (QA-F4-001).
     *
     * Borrado idempotente: una ruta inexistente en un disco se omite; un
     * archivo existente que no puede borrarse aborta la operación (QA-F4-002).
     *
     * @throws RuntimeException si un archivo existente no puede borrarse.
     */
    private function borrarAdjuntos(Model $documento): void
    {
        $ruta = $documento->archivo;

        foreach (array_unique([$documento->discoArchivo(), 'public']) as $disco) {
            if (! Storage::disk($disco)->exists($ruta)) {
                continue;
            }

            if (! Storage::disk($disco)->delete($ruta)) {
                throw new RuntimeException("No se pudo eliminar el archivo «{$ruta}» del disco {$disco}.");
            }
        }
    }

    /**
     * Relee el documento dentro de la transacción, con bloqueo de fila, y
     * revalida que siga en papelera antes de aplicar una transición
     * incompatible (QA-F4-009).
     *
     * @return Model|null el documento aún en papelera, o null si otra
     *                    operación (restauración o purga) ya ganó la contención.
     */
    private function releerEnPapelera(Model $documento): ?Model
    {
        return $documento->newQuery()
            ->onlyTrashed()
            ->whereKey($documento->getKey())
            ->lockForUpdate()
            ->first();
    }

    /**
     * Resolve a trashed document enforcing the HU-008 permission matrix.
     */
    private function trashed(Request $request, string $modulo, int $id): mixed
    {
        $clase = DocumentosPermitidos::MODULOS[$modulo] ?? null;

        abort_unless($clase !== null, 404);
        abort_unless(DocumentosPermitidos::puedeEliminar($request->user(), $clase), 403);

        return $clase::query()->onlyTrashed()->findOrFail($id);
    }
}
