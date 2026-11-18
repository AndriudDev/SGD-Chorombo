<?php

namespace App\Http\Controllers\Auditoria;

use App\Http\Controllers\Controller;
use App\Models\PermisoAdministrativo;
use App\Models\User;
use App\Services\DocumentosPermitidos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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

            $documentos = $clase::query()
                ->onlyTrashed()
                ->with($clase === PermisoAdministrativo::class ? 'creador' : 'user')
                ->orderBy('deleted_at', 'desc')
                ->limit(self::POR_PAGINA)
                ->get();

            if ($documentos->isNotEmpty()) {
                $secciones[$modulo] = $documentos;
            }
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
        $documento->restore();

        return back()->with('success', "«{$documento->etiquetaAuditoria()}» restaurado correctamente.");
    }

    /**
     * Permanently delete a document from the papelera, including its file (HU-08).
     */
    public function destroy(Request $request, string $modulo, int $id): RedirectResponse
    {
        $documento = $this->trashed($request, $modulo, $id);

        if ($documento->archivo) {
            Storage::disk($documento->discoArchivo())->delete($documento->archivo);
        }

        $documento->forceDelete();

        return back()->with('success', "«{$documento->etiquetaAuditoria()}» eliminado definitivamente.");
    }

    /**
     * Resolve a trashed document enforcing the HU-08 permission matrix.
     */
    private function trashed(Request $request, string $modulo, int $id): mixed
    {
        $clase = DocumentosPermitidos::MODULOS[$modulo] ?? null;

        abort_unless($clase !== null, 404);
        abort_unless(DocumentosPermitidos::puedeEliminar($request->user(), $clase), 403);

        return $clase::query()->onlyTrashed()->findOrFail($id);
    }
}
