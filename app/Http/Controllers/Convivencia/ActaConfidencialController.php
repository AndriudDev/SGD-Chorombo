<?php

namespace App\Http\Controllers\Convivencia;

use App\Http\Controllers\Controller;
use App\Http\Requests\Convivencia\StoreActaConfidencialRequest;
use App\Http\Requests\Convivencia\UpdateActaConfidencialRequest;
use App\Models\ActaConfidencial;
use App\Models\Auditoria;
use App\Services\ArchivoService;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActaConfidencialController extends Controller
{
    public function __construct(
        private ArchivoService $archivoService,
        private AuditoriaService $auditoria
    ) {}

    /**
     * List confidential actas (HU-05: la ruta solo admite Convivencia y Dirección).
     */
    public function index(): View
    {
        $query = ActaConfidencial::with('user');

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
        // QA-F4-005: la traza de auditoría (observer created) forma parte de la
        // misma transacción; si no puede registrarse, el acta no se crea.
        $acta = DB::transaction(fn (): ActaConfidencial => ActaConfidencial::create([
            'titulo' => $request->titulo,
            'categoria' => $request->categoria,
            'ambito' => $request->ambito,
            'estudiante_nombre' => $request->estudiante_nombre,
            'estudiante_run' => $request->estudiante_run,
            'apoderado_nombre' => $request->apoderado_nombre,
            'fecha' => $request->fecha,
            'numero_reserva' => Auth::user()->hasRole('directora') ? ($request->input('numero_reserva') ?: null) : null,
            'user_id' => auth()->id(),
        ]));

        $acta->archivo = $this->archivoService->subir(
            $request->file('archivo'),
            'actas',
            "acta-{$acta->id}-{$acta->estudiante_run}",
            'local',
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
        $datos = [
            'titulo' => $request->titulo,
            'categoria' => $request->categoria,
            'ambito' => $request->ambito,
            'estudiante_nombre' => $request->estudiante_nombre,
            'estudiante_run' => $request->estudiante_run,
            'apoderado_nombre' => $request->apoderado_nombre,
            'fecha' => $request->fecha,
        ];

        // La numeración reservada la asigna y modifica únicamente Dirección (HU-05).
        if (Auth::user()->hasRole('directora')) {
            $datos['numero_reserva'] = $request->input('numero_reserva') ?: null;
        }

        // QA-F4-005: la traza de auditoría (observer updated) forma parte de la
        // misma transacción; si no puede registrarse, la edición no se aplica.
        DB::transaction(fn () => $acta->update($datos));

        if ($request->hasFile('archivo')) {
            $this->archivoService->eliminar($acta->archivo, 'local');
            $acta->archivo = $this->archivoService->subir(
                $request->file('archivo'),
                'actas',
                "acta-{$acta->id}-{$acta->estudiante_run}",
                'local',
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
        // HU-08: eliminación lógica (papelera); el archivo privado se conserva hasta la purga definitiva.
        // QA-F4-005: la eliminación exige su traza en la misma transacción.
        DB::transaction(fn () => $acta->delete());

        return redirect()->route('actas.index')
            ->with('success', 'Acta confidencial enviada a la papelera. Puede recuperarse desde allí.');
    }

    /**
     * Download the acta file from private storage (HU-05: sin enlaces públicos).
     */
    public function download(ActaConfidencial $acta): StreamedResponse
    {
        // QA-F3-001: los archivos históricos quedaron en el disco público antes de la
        // migración a private; se trasladan al disco privado la primera vez que se piden.
        if ($acta->archivo
            && ! Storage::disk('local')->exists($acta->archivo)
            && Storage::disk('public')->exists($acta->archivo)) {
            Storage::disk('local')->put($acta->archivo, Storage::disk('public')->get($acta->archivo));
            Storage::disk('public')->delete($acta->archivo);
        }

        if (! $acta->archivo || ! Storage::disk('local')->exists($acta->archivo)) {
            abort(404, 'El archivo del acta no está disponible.');
        }

        $this->auditoria->registrar(Auditoria::ACCION_DESCARGAR, $acta);

        return Storage::disk('local')->download($acta->archivo);
    }
}
