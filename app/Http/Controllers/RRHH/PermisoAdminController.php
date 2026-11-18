<?php

namespace App\Http\Controllers\RRHH;

use App\Http\Controllers\Controller;
use App\Http\Requests\RRHH\StorePermisoAdminRequest;
use App\Http\Requests\RRHH\UpdatePermisoAdminRequest;
use App\Models\Auditoria;
use App\Models\PermisoAdministrativo;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\CorrespondenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PermisoAdminController extends Controller
{
    public function __construct(
        private CorrespondenciaService $correspondenciaService,
        private AuditoriaService $auditoria
    ) {}

    /**
     * Record who performed a state change on a permiso (REV-03 audit trail).
     * Call it after the state update, inside the same transaction: the row's
     * user_id is the actor, never the approver of record (`aprobado_por`).
     */
    private function registrarTransicion(PermisoAdministrativo $permiso, ?string $estadoAnterior): void
    {
        $permiso->transiciones()->create([
            'user_id' => auth()->id(),
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $permiso->estado,
        ]);
    }

    /**
     * List permisos with optional filters.
     */
    public function index(): View
    {
        $user = Auth::user();
        $year = request()->input('year', date('Y'));
        $esSupervisor = $user->hasAnyRole(['directora', 'administracion', 'inspectoria']);
        $puedeRevocar = $user->hasAnyRole(['directora', 'administracion']);

        $query = PermisoAdministrativo::with(['user', 'aprobador', 'ultimaTransicion.user']);

        // Secretaría, UTP y Convivencia solo ven sus propios permisos
        if (! $esSupervisor) {
            $query->where('user_id', $user->id);
        }

        if ($estado = request()->input('estado')) {
            $query->byEstado($estado);
        }

        $permisos = $query->orderBy('created_at', 'desc')->paginate(10);
        $diasAcumulados = $this->correspondenciaService->calcularDiasAcumulados($user->id, $year);

        // Para supervisores: acumulado por funcionario visible en la tabla
        $acumuladosPorUsuario = [];
        if ($esSupervisor) {
            $userIds = collect($permisos->items())->pluck('user_id')->unique();
            foreach ($userIds as $uid) {
                $acumuladosPorUsuario[$uid] = $this->correspondenciaService->calcularDiasAcumulados($uid, $year);
            }
        }

        // Indicador de solicitudes pendientes para perfiles autorizados (HU-03)
        $pendientesCount = $esSupervisor
            ? PermisoAdministrativo::where('estado', 'pendiente')->count()
            : null;

        return view('permisos-administrativos.index', compact('permisos', 'diasAcumulados', 'acumuladosPorUsuario', 'pendientesCount', 'puedeRevocar'))
            ->with('cupo', PermisoAdministrativo::CUPO_ANUAL);
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        $users = null;
        if (Auth::user()->hasAnyRole(['directora', 'administracion', 'inspectoria'])) {
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

        // Admin/Directora/Inspectoría pueden crear permisos para otro usuario
        $userId = $user->hasAnyRole(['directora', 'administracion', 'inspectoria'])
            ? $request->user_id
            : $user->id;

        $permiso = PermisoAdministrativo::create([
            'user_id' => $userId,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'tipo_jornada' => $request->tipo_jornada,
            'motivo' => $request->motivo,
            'estado' => 'pendiente',
            'created_by' => $user->id,
        ]);

        if ($request->hasFile('archivo')) {
            $permiso->archivo = $this->correspondenciaService->subirRespaldoPermiso($request->file('archivo'), $permiso);
            $permiso->save();
        }

        $this->registrarTransicion($permiso, null);

        return redirect()->route('permisos.index')
            ->with('success', 'Solicitud de permiso creada correctamente.');
    }

    /**
     * Show edit form.
     */
    public function edit(PermisoAdministrativo $permiso): View
    {
        $users = null;
        if (Auth::user()->hasAnyRole(['directora', 'administracion', 'inspectoria'])) {
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

        // Admin/Directora/Inspectoría pueden cambiar el funcionario al editar
        if (Auth::user()->hasAnyRole(['directora', 'administracion', 'inspectoria']) && $request->filled('user_id')) {
            $permiso->update(['user_id' => $request->user_id]);
        }

        if ($request->hasFile('archivo')) {
            $this->correspondenciaService->eliminarRespaldoPermiso($permiso);
            $permiso->archivo = $this->correspondenciaService->subirRespaldoPermiso($request->file('archivo'), $permiso);
            $permiso->save();
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

        // HU-08: eliminación lógica (papelera); el respaldo se conserva hasta la purga definitiva.
        $permiso->delete();

        return redirect()->route('permisos.index')
            ->with('success', 'Solicitud de permiso enviada a la papelera. Puede recuperarse desde allí.');
    }

    /**
     * Download the supporting document (respaldo) of a permiso.
     * Supervisors (Dirección, Administración, Inspectoría) or the owner.
     */
    public function download(PermisoAdministrativo $permiso): StreamedResponse
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['directora', 'administracion', 'inspectoria']) && $permiso->user_id !== $user->id) {
            abort(403, 'No tienes permiso para descargar este respaldo.');
        }

        if (! $permiso->archivo || ! Storage::disk('public')->exists($permiso->archivo)) {
            abort(404, 'El respaldo adjunto no está disponible.');
        }

        $this->auditoria->registrar(Auditoria::ACCION_DESCARGAR, $permiso);

        return Storage::disk('public')->download($permiso->archivo);
    }

    /**
     * Approve a permiso request.
     */
    public function aprobar(PermisoAdministrativo $permiso): RedirectResponse
    {
        if (! $permiso->puedePasarA(PermisoAdministrativo::ESTADO_APROBADO)) {
            return back()->with('error', "No se puede aprobar una solicitud en estado \"{$permiso->estado}\".");
        }

        DB::transaction(function () use ($permiso): void {
            $estadoAnterior = $permiso->estado;

            $permiso->update([
                'estado' => 'aprobado',
                'aprobado_por' => auth()->id(),
            ]);

            $this->registrarTransicion($permiso, $estadoAnterior);
        });

        return redirect()->route('permisos.index')
            ->with('success', 'Permiso aprobado correctamente.');
    }

    /**
     * Reject a permiso request.
     */
    public function rechazar(PermisoAdministrativo $permiso): RedirectResponse
    {
        if ($permiso->estado === PermisoAdministrativo::ESTADO_APROBADO) {
            return back()->with('error', 'Una solicitud aprobada no puede rechazarse. Use el flujo de revocación.');
        }

        if (! $permiso->puedePasarA(PermisoAdministrativo::ESTADO_RECHAZADO)) {
            return back()->with('error', "No se puede rechazar una solicitud en estado \"{$permiso->estado}\".");
        }

        DB::transaction(function () use ($permiso): void {
            $estadoAnterior = $permiso->estado;

            $permiso->update([
                'estado' => 'rechazado',
            ]);

            $this->registrarTransicion($permiso, $estadoAnterior);
        });

        return redirect()->route('permisos.index')
            ->with('success', 'Permiso rechazado correctamente.');
    }

    /**
     * Revoke an approved request through the formal revocation flow
     * (Dirección / Administración only). The returned days are restored
     * automatically because the balance only counts approved requests.
     */
    public function revocar(PermisoAdministrativo $permiso): RedirectResponse
    {
        if (! $permiso->puedePasarA(PermisoAdministrativo::ESTADO_REVOCADA)) {
            return back()->with('error', 'Solo se pueden revocar solicitudes aprobadas.');
        }

        DB::transaction(function () use ($permiso): void {
            $estadoAnterior = $permiso->estado;

            $permiso->update([
                'estado' => PermisoAdministrativo::ESTADO_REVOCADA,
            ]);

            $this->registrarTransicion($permiso, $estadoAnterior);
        });

        return redirect()->route('permisos.index')
            ->with('success', 'Permiso revocado correctamente. Los días vuelven al saldo del funcionario.');
    }
}
