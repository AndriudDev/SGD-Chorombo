<?php

namespace App\Http\Controllers\RRHH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RRHH\StorePermisoAdminRequest;
use App\Http\Requests\RRHH\UpdatePermisoAdminRequest;
use App\Models\PermisoAdministrativo;
use App\Models\User;
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
        $year = request()->input('year', date('Y'));

        $query = PermisoAdministrativo::with('user', 'aprobador');

        // Secretaría, UTP y Convivencia solo ven sus propios permisos
        if (! $user->hasAnyRole(['directora', 'administracion'])) {
            $query->where('user_id', $user->id);
        }

        if ($estado = request()->input('estado')) {
            $query->byEstado($estado);
        }

        $permisos = $query->orderBy('created_at', 'desc')->paginate(10);
        $diasAcumulados = $this->correspondenciaService->calcularDiasAcumulados($user->id, $year);

        // Para supervisores: acumulado por funcionario visible en la tabla
        $acumuladosPorUsuario = [];
        if ($user->hasAnyRole(['directora', 'administracion'])) {
            $userIds = collect($permisos->items())->pluck('user_id')->unique();
            foreach ($userIds as $uid) {
                $acumuladosPorUsuario[$uid] = $this->correspondenciaService->calcularDiasAcumulados($uid, $year);
            }
        }

        return view('permisos-administrativos.index', compact('permisos', 'diasAcumulados', 'acumuladosPorUsuario'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        $users = null;
        if (Auth::user()->hasAnyRole(['directora', 'administracion'])) {
            $users = User::with('roles')->orderBy('name')->get();
        }

        return view('permisos-administrativos.form', [
            'permiso' => null,
            'users' => $users,
        ]);
    }

    /**
     * Store a new permiso request.
     */
    public function store(StorePermisoAdminRequest $request): RedirectResponse
    {
        $user = Auth::user();

        // Admin/Directora pueden crear permisos para otro usuario
        $userId = $user->hasAnyRole(['directora', 'administracion'])
            ? $request->user_id
            : $user->id;

        PermisoAdministrativo::create([
            'user_id' => $userId,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'tipo_jornada' => $request->tipo_jornada,
            'motivo' => $request->motivo,
            'estado' => 'pendiente',
            'created_by' => $user->id,
        ]);

        return redirect()->route('permisos.index')
            ->with('success', 'Solicitud de permiso creada correctamente.');
    }

    /**
     * Show edit form.
     */
    public function edit(PermisoAdministrativo $permiso): View
    {
        $users = null;
        if (Auth::user()->hasAnyRole(['directora', 'administracion'])) {
            $users = User::with('roles')->orderBy('name')->get();
        }

        return view('permisos-administrativos.form', compact('permiso', 'users'));
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
            'fecha_fin' => $request->fecha_fin,
            'tipo_jornada' => $request->tipo_jornada,
            'motivo' => $request->motivo,
        ]);

        // Admin/Directora pueden cambiar el funcionario al editar
        if (Auth::user()->hasAnyRole(['directora', 'administracion']) && $request->filled('user_id')) {
            $permiso->update(['user_id' => $request->user_id]);
        }

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
        $permiso->update([
            'estado' => 'aprobado',
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
        $permiso->update([
            'estado' => 'rechazado',
            'aprobado_por' => auth()->id(),
        ]);

        return redirect()->route('permisos.index')
            ->with('success', 'Permiso rechazado correctamente.');
    }
}
