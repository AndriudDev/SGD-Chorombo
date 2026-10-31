<?php

namespace App\Http\Controllers\Convivencia;

use App\Http\Controllers\Controller;
use App\Http\Requests\Convivencia\StoreCitacionRequest;
use App\Http\Requests\Convivencia\UpdateCitacionRequest;
use App\Http\Requests\Convivencia\UpdateEstadoCitacionRequest;
use App\Models\Citacion;
use App\Services\ArchivoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CitacionController extends Controller
{
    public function __construct(
        private ArchivoService $archivoService
    ) {}

    /**
     * List citaciones with optional filters (estado, motivo, search).
     */
    public function index(): View
    {
        $query = Citacion::with('user');

        if ($estado = request()->input('estado')) {
            $query->byEstado($estado);
        }
        if ($motivo = request()->input('motivo')) {
            $query->byMotivo($motivo);
        }
        if ($search = request()->input('search')) {
            $query->search($search);
        }

        $citaciones = $query->orderBy('fecha', 'desc')->orderBy('hora', 'desc')->paginate(10);

        return view('citaciones.index', compact('citaciones'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('citaciones.form', [
            'citacion' => null,
            'funcionarioDefault' => Auth::user()->name,
        ]);
    }

    /**
     * Store a new citación.
     */
    public function store(StoreCitacionRequest $request): RedirectResponse
    {
        $citacion = Citacion::create([
            'estudiante_nombre' => $request->estudiante_nombre,
            'estudiante_run' => $request->estudiante_run,
            'apoderado_nombre' => $request->apoderado_nombre,
            'funcionario_cita' => $request->funcionario_cita,
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'motivo' => $request->motivo,
            'estado' => 'programada',
            'user_id' => auth()->id(),
        ]);

        if ($request->hasFile('archivo')) {
            $citacion->archivo = $this->archivoService->subir(
                $request->file('archivo'),
                'citaciones',
                "citacion-{$citacion->id}-{$citacion->estudiante_run}",
            );
            $citacion->save();
        }

        return redirect()->route('citaciones.index')
            ->with('success', 'Citación registrada correctamente.');
    }

    /**
     * Show a single citación.
     */
    public function show(Citacion $citacion): View
    {
        $citacion->load('user');

        return view('citaciones.show', compact('citacion'));
    }

    /**
     * Show edit form.
     */
    public function edit(Citacion $citacion): View
    {
        return view('citaciones.form', [
            'citacion' => $citacion,
            'funcionarioDefault' => $citacion->funcionario_cita,
        ]);
    }

    /**
     * Update a citación.
     */
    public function update(UpdateCitacionRequest $request, Citacion $citacion): RedirectResponse
    {
        $citacion->update([
            'estudiante_nombre' => $request->estudiante_nombre,
            'estudiante_run' => $request->estudiante_run,
            'apoderado_nombre' => $request->apoderado_nombre,
            'funcionario_cita' => $request->funcionario_cita,
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'motivo' => $request->motivo,
        ]);

        if ($request->hasFile('archivo')) {
            $this->archivoService->eliminar($citacion->archivo);
            $citacion->archivo = $this->archivoService->subir(
                $request->file('archivo'),
                'citaciones',
                "citacion-{$citacion->id}-{$citacion->estudiante_run}",
            );
            $citacion->save();
        }

        return redirect()->route('citaciones.index')
            ->with('success', 'Citación actualizada correctamente.');
    }

    /**
     * Delete a citación.
     */
    public function destroy(Citacion $citacion): RedirectResponse
    {
        $this->archivoService->eliminar($citacion->archivo);
        $citacion->delete();

        return redirect()->route('citaciones.index')
            ->with('success', 'Citación eliminada correctamente.');
    }

    /**
     * Update the seguimiento state of a citación (HU-04).
     */
    public function actualizarEstado(UpdateEstadoCitacionRequest $request, Citacion $citacion): RedirectResponse
    {
        $citacion->update(['estado' => $request->estado]);

        return redirect()->route('citaciones.show', $citacion)
            ->with('success', 'Estado de la citación actualizado correctamente.');
    }

    /**
     * Download the citación attachment.
     */
    public function download(Citacion $citacion): StreamedResponse
    {
        return Storage::disk('public')->download($citacion->archivo);
    }
}
