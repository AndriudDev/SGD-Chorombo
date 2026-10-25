<?php

namespace App\Http\Controllers\RRHH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RRHH\StorePermisoAdminRequest;
use App\Http\Requests\RRHH\UpdatePermisoAdminRequest;
use App\Models\PermisoAdministrativo;
use App\Services\CorrespondenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PermisoAdminController extends Controller
{
    public function __construct(
        private CorrespondenciaService $correspondenciaService
    ) {}

    /**
     * List permisos with optional filters.
     */
    public function index(): View
    {
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['administracion', 'directora']);
        $year = request()->input('year', date('Y'));

        $query = PermisoAdministrativo::with('user', 'aprobador');

        if (!$isAdmin) {
            $query->byUser($user->id);
        } elseif ($estado = request()->input('estado')) {
            $query->byEstado($estado);
        }

        $permisos = $query->orderBy('created_at', 'desc')->paginate(10);
        $diasAcumulados = $this->correspondenciaService->calcularDiasAcumulados($user->id, $year);
        $showAprobar = $isAdmin;

        return view('permisos-administrativos.index', compact('permisos', 'diasAcumulados', 'showAprobar'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('permisos-administrativos.form', ['permiso' => null]);
    }

    /**
     * Store a new permiso request.
     */
    public function store(StorePermisoAdminRequest $request): RedirectResponse
    {
        PermisoAdministrativo::create([
            'user_id'       => auth()->id(),
            'fecha_inicio'  => $request->fecha_inicio,
            'fecha_fin'     => $request->fecha_fin,
            'tipo_jornada'  => $request->tipo_jornada,
            'motivo'        => $request->motivo,
            'estado'        => 'pendiente',
            'created_by'    => auth()->id(),
        ]);

        return redirect()->route('permisos.index')
            ->with('success', 'Solicitud de permiso creada correctamente.');
    }

    /**
     * Show edit form.
     */
    public function edit(PermisoAdministrativo $permiso): View
    {
        return view('permisos-administrativos.form', compact('permiso'));
    }

    /**
     * Update a permiso request (only if pending).
     */
    public function update(UpdatePermisoAdminRequest $request, PermisoAdministrativo $permiso): RedirectResponse
    {
        if ($permiso->estado !== 'pendiente') {
            return back()->with('error', 'No se puede modificar un permiso ya procesado.');
        }

        $permiso->update([
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'tipo_jornada' => $request->tipo_jornada,
            'motivo'       => $request->motivo,
        ]);

        return redirect()->route('permisos.index')
            ->with('success', 'Solicitud de permiso actualizada correctamente.');
    }

    /**
     * Delete a permiso request (only if pending).
     */
    public function destroy(PermisoAdministrativo $permiso): RedirectResponse
    {
        if ($permiso->estado !== 'pendiente') {
            return back()->with('error', 'No se puede eliminar un permiso ya procesado.');
        }

        $permiso->delete();

        return redirect()->route('permisos.index')
            ->with('success', 'Solicitud de permiso eliminada correctamente.');
    }

    /**
     * Approve a permiso request.
     */
    public function aprobar(PermisoAdministrativo $permiso): RedirectResponse
    {
        if (!Auth::user()->hasAnyRole(['administracion', 'directora'])) {
            return back()->with('error', 'No tienes permisos para aprobar permisos.');
        }

        $permiso->update([
            'estado'       => 'aprobado',
            'aprobado_por' => auth()->id(),
        ]);

        return redirect()->route('permisos.index')
            ->with('success', 'Permiso aprobado correctamente.');
    }

    /**
     * Reject a permiso request.
     */
    public function rechazar(PermisoAdministrativo $permiso): RedirectResponse
    {
        if (!Auth::user()->hasAnyRole(['administracion', 'directora'])) {
            return back()->with('error', 'No tienes permisos para rechazar permisos.');
        }

        $permiso->update([
            'estado'       => 'rechazado',
            'aprobado_por' => auth()->id(),
        ]);

        return redirect()->route('permisos.index')
            ->with('success', 'Permiso rechazado correctamente.');
    }
}