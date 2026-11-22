<?php

namespace App\Http\Controllers\UTP;

use App\Http\Controllers\Controller;
use App\Http\Requests\UTP\StoreReunionComunalRequest;
use App\Http\Requests\UTP\UpdateReunionComunalRequest;
use App\Models\Auditoria;
use App\Models\ReunionComunal;
use App\Services\ArchivoService;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReunionComunalController extends Controller
{
    public function __construct(
        private ArchivoService $archivoService,
        private AuditoriaService $auditoria
    ) {}

    /**
     * List reuniones comunales with optional filters.
     */
    public function index(): View
    {
        $query = ReunionComunal::withCount('compromisos');

        if ($categoria = request()->input('categoria')) {
            $query->byCategoria($categoria);
        }
        if ($fecha = request()->input('fecha')) {
            $query->whereDate('fecha', $fecha);
        }
        if ($search = request()->input('search')) {
            $query->search($search);
        }

        $reuniones = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('reuniones-comunales.index', compact('reuniones'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('reuniones-comunales.form', ['reunion' => null]);
    }

    /**
     * Store a new reunión comunal.
     */
    public function store(StoreReunionComunalRequest $request): RedirectResponse
    {
        // QA-F4-005: la traza de auditoría (observer created) forma parte de la
        // misma transacción; si no puede registrarse, la reunión no se crea.
        $reunion = DB::transaction(fn (): ReunionComunal => ReunionComunal::create([
            'titulo' => $request->titulo,
            'categoria' => $request->categoria,
            'fecha' => $request->fecha,
            'descripcion' => $request->descripcion,
            'user_id' => auth()->id(),
        ]));

        if ($request->hasFile('archivo')) {
            $reunion->archivo = $this->archivoService->subir(
                $request->file('archivo'),
                'reuniones',
                "reunion-{$reunion->id}-{$reunion->categoria}",
            );
            $reunion->save();
        }

        return redirect()->route('comunales.show', $reunion)
            ->with('success', 'Reunión comunal registrada correctamente.');
    }

    /**
     * Show a single reunión with its compromisos.
     */
    public function show(ReunionComunal $reunion): View
    {
        $reunion->load('user', 'compromisos');

        return view('reuniones-comunales.show', compact('reunion'));
    }

    /**
     * Show edit form.
     */
    public function edit(ReunionComunal $reunion): View
    {
        return view('reuniones-comunales.form', compact('reunion'));
    }

    /**
     * Update a reunión comunal.
     */
    public function update(UpdateReunionComunalRequest $request, ReunionComunal $reunion): RedirectResponse
    {
        // QA-F4-005: la traza de auditoría (observer updated) forma parte de la
        // misma transacción; si no puede registrarse, la edición no se aplica.
        DB::transaction(fn () => $reunion->update([
            'titulo' => $request->titulo,
            'categoria' => $request->categoria,
            'fecha' => $request->fecha,
            'descripcion' => $request->descripcion,
        ]));

        if ($request->hasFile('archivo')) {
            $this->archivoService->eliminar($reunion->archivo);
            $reunion->archivo = $this->archivoService->subir(
                $request->file('archivo'),
                'reuniones',
                "reunion-{$reunion->id}-{$reunion->categoria}",
            );
            $reunion->save();
        }

        return redirect()->route('comunales.show', $reunion)
            ->with('success', 'Reunión comunal actualizada correctamente.');
    }

    /**
     * Delete a reunión comunal (compromisos cascade with it).
     */
    public function destroy(ReunionComunal $reunion): RedirectResponse
    {
        // HU-08: eliminación lógica (papelera); el adjunto se conserva hasta la purga definitiva.
        // QA-F4-005: la eliminación exige su traza en la misma transacción.
        DB::transaction(fn () => $reunion->delete());

        return redirect()->route('comunales.index')
            ->with('success', 'Reunión comunal enviada a la papelera. Puede recuperarse desde allí.');
    }

    /**
     * Download the reunión attachment.
     */
    public function download(ReunionComunal $reunion): StreamedResponse
    {
        if (! $reunion->archivo || ! Storage::disk('public')->exists($reunion->archivo)) {
            abort(404, 'El documento de la reunión no está disponible.');
        }

        $this->auditoria->registrar(Auditoria::ACCION_DESCARGAR, $reunion);

        return Storage::disk('public')->download($reunion->archivo);
    }
}
