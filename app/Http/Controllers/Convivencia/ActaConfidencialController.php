<?php

namespace App\Http\Controllers\Convivencia;

use App\Http\Controllers\Controller;
use App\Http\Requests\Convivencia\StoreActaConfidencialRequest;
use App\Http\Requests\Convivencia\UpdateActaConfidencialRequest;
use App\Models\ActaConfidencial;
use App\Services\ArchivoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActaConfidencialController extends Controller
{
    public function __construct(
        private ArchivoService $archivoService
    ) {}

    /**
     * List confidential actas.
     * UTP only sees actas of pedagogical ámbito (HU-05).
     */
    public function index(): View
    {
        $query = ActaConfidencial::with('user');

        if (Auth::user()->hasRole('utp')) {
            $query->byAmbito('pedagogico');
        }
        if ($ambito = request()->input('ambito')) {
            $query->byAmbito($ambito);
        }
        if ($categoria = request()->input('categoria')) {
            $query->byCategoria($categoria);
        }
        if ($search = request()->input('search')) {
            $query->search($search);
        }

        $actas = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('actas-confidenciales.index', compact('actas'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('actas-confidenciales.form', ['acta' => null]);
    }

    /**
     * Store a new confidential acta.
     */
    public function store(StoreActaConfidencialRequest $request): RedirectResponse
    {
        $acta = ActaConfidencial::create([
            'titulo' => $request->titulo,
            'categoria' => $request->categoria,
            'ambito' => $request->ambito,
            'estudiante_nombre' => $request->estudiante_nombre,
            'estudiante_run' => $request->estudiante_run,
            'apoderado_nombre' => $request->apoderado_nombre,
            'fecha' => $request->fecha,
            'user_id' => auth()->id(),
        ]);

        $acta->archivo = $this->archivoService->subir(
            $request->file('archivo'),
            'actas',
            "acta-{$acta->id}-{$acta->estudiante_run}",
        );
        $acta->save();

        return redirect()->route('actas.index')
            ->with('success', 'Acta confidencial registrada correctamente.');
    }

    /**
     * Show edit form.
     */
    public function edit(ActaConfidencial $acta): View
    {
        return view('actas-confidenciales.form', compact('acta'));
    }

    /**
     * Update a confidential acta.
     */
    public function update(UpdateActaConfidencialRequest $request, ActaConfidencial $acta): RedirectResponse
    {
        $acta->update([
            'titulo' => $request->titulo,
            'categoria' => $request->categoria,
            'ambito' => $request->ambito,
            'estudiante_nombre' => $request->estudiante_nombre,
            'estudiante_run' => $request->estudiante_run,
            'apoderado_nombre' => $request->apoderado_nombre,
            'fecha' => $request->fecha,
        ]);

        if ($request->hasFile('archivo')) {
            $this->archivoService->eliminar($acta->archivo);
            $acta->archivo = $this->archivoService->subir(
                $request->file('archivo'),
                'actas',
                "acta-{$acta->id}-{$acta->estudiante_run}",
            );
            $acta->save();
        }

        return redirect()->route('actas.index')
            ->with('success', 'Acta confidencial actualizada correctamente.');
    }

    /**
     * Delete a confidential acta.
     */
    public function destroy(ActaConfidencial $acta): RedirectResponse
    {
        $this->archivoService->eliminar($acta->archivo);
        $acta->delete();

        return redirect()->route('actas.index')
            ->with('success', 'Acta confidencial eliminada correctamente.');
    }

    /**
     * Download the acta file. UTP can only download pedagogical actas (HU-05).
     */
    public function download(ActaConfidencial $acta): StreamedResponse
    {
        $this->autorizarConsultaUtp($acta);

        return Storage::disk('public')->download($acta->archivo);
    }

    /**
     * Abort with 403 when a UTP user tries to reach a non-pedagogical acta.
     */
    private function autorizarConsultaUtp(ActaConfidencial $acta): void
    {
        if (Auth::user()->hasRole('utp') && $acta->ambito !== 'pedagogico') {
            abort(403, 'Las actas de ámbito no pedagógico no son visibles para la Jefatura de UTP.');
        }
    }
}
