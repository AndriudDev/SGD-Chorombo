<?php

namespace App\Http\Controllers\Auditoria;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use App\Services\DocumentosPermitidos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    /** Registros por página de la bitácora (HU-08). */
    private const POR_PAGINA = 20;

    /**
     * Audit trail with filters (HU-08: quién realizó la acción y cuándo).
     *
     * Rows are limited to the modules the user may read, so the bitácora
     * itself never reveals reserved documents (HU-05).
     */
    public function index(Request $request): View
    {
        $datos = $request->validate([
            'usuario' => ['nullable', 'integer', 'exists:users,id'],
            'accion' => ['nullable', Rule::in(array_keys(Auditoria::ACCIONES))],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'modulo' => ['nullable', Rule::in(array_keys(DocumentosPermitidos::MODULOS))],
        ]);

        $legibles = DocumentosPermitidos::legiblesPara($request->user());

        $clases = ($datos['modulo'] ?? null)
            ? DocumentosPermitidos::clasesDeModulo($datos['modulo'])
            : array_keys($legibles);
        $clases = array_values(array_intersect($clases, array_keys($legibles)));

        $registros = Auditoria::with('user')
            ->deModelos($clases)
            ->entreFechas($datos['fecha_desde'] ?? null, $datos['fecha_hasta'] ?? null)
            ->when($datos['usuario'] ?? null, fn ($query, $id) => $query->byUsuario((int) $id))
            ->when($datos['accion'] ?? null, fn ($query, $accion) => $query->byAccion($accion))
            ->orderBy('id', 'desc')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('auditoria.index', [
            'registros' => $registros,
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
            'acciones' => Auditoria::ACCIONES,
            'modulos' => DocumentosPermitidos::ETIQUETAS,
        ]);
    }
}
