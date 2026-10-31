<?php

namespace App\Http\Controllers\UTP;

use App\Http\Controllers\Controller;
use App\Http\Requests\UTP\StoreCompromisoRequest;
use App\Http\Requests\UTP\UpdateCompromisoRequest;
use App\Models\CompromisoReunion;
use App\Models\ReunionComunal;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompromisoController extends Controller
{
    /**
     * Register a compromiso for a reunión (HU-06).
     */
    public function store(StoreCompromisoRequest $request, ReunionComunal $reunion): RedirectResponse
    {
        $reunion->compromisos()->create([
            'descripcion' => $request->descripcion,
            'fecha_limite' => $request->fecha_limite,
        ]);

        return redirect()->route('comunales.show', $reunion)
            ->with('success', 'Compromiso registrado correctamente.');
    }

    /**
     * Show edit form for a compromiso of a reunión.
     */
    public function edit(ReunionComunal $reunion, CompromisoReunion $compromiso): View
    {
        $this->verificarPertenencia($reunion, $compromiso);

        return view('reuniones-comunales.compromiso-form', compact('reunion', 'compromiso'));
    }

    /**
     * Update a compromiso of a reunión.
     */
    public function update(UpdateCompromisoRequest $request, ReunionComunal $reunion, CompromisoReunion $compromiso): RedirectResponse
    {
        $this->verificarPertenencia($reunion, $compromiso);

        $compromiso->update([
            'descripcion' => $request->descripcion,
            'fecha_limite' => $request->fecha_limite,
        ]);

        return redirect()->route('comunales.show', $reunion)
            ->with('success', 'Compromiso actualizado correctamente.');
    }

    /**
     * Delete a compromiso of a reunión.
     */
    public function destroy(ReunionComunal $reunion, CompromisoReunion $compromiso): RedirectResponse
    {
        $this->verificarPertenencia($reunion, $compromiso);

        $compromiso->delete();

        return redirect()->route('comunales.show', $reunion)
            ->with('success', 'Compromiso eliminado correctamente.');
    }

    /**
     * Ensure the compromiso belongs to the route's reunión.
     */
    private function verificarPertenencia(ReunionComunal $reunion, CompromisoReunion $compromiso): void
    {
        if ($compromiso->reunion_id !== $reunion->id) {
            abort(404, 'El compromiso no pertenece a esta reunión.');
        }
    }
}
