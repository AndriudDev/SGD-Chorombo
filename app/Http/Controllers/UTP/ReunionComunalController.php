<?php

namespace App\Http\Controllers\UTP;

use App\Http\Controllers\Controller;
use App\Http\Requests\UTP\StoreReunionComunalRequest;
use App\Http\Requests\UTP\UpdateReunionComunalRequest;
use App\Models\ReunionComunal;
use App\Services\ArchivoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReunionComunalController extends Controller
{
    public function __construct(
        private ArchivoService $archivoService
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
        $reunion = ReunionComunal::create([
            'titulo' => $request->titulo,
            'categoria' => $request->categoria,
            'fecha' => $request->fecha,
            'descripcion' => $request->descripcion,
            'user_id' => auth()->id(),
        ]);

        $reunion->archivo = $this->archivoService->subir(
            $request->file('archivo'),
            'reuniones',
            "reunion-{$reunion->id}-{$reunion->categoria}",
        );
        $reunion->save();

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
        $reunion->update([
            'titulo' => $request->titulo,
            'categoria' => $request->categoria,
            'fecha' => $request->fecha,
            'descripcion' => $request->descripcion,
        ]);

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
        $this->archivoService->eliminar($reunion->archivo);
        $reunion->delete();

        return redirect()->route('comunales.index')
            ->with('success', 'Reunión comunal eliminada correctamente.');
    }

    /**
     * Download the reunión attachment.
     */
    public function download(ReunionComunal $reunion): StreamedResponse
    {
        return Storage::disk('public')->download($reunion->archivo);
    }
}
