<?php

namespace App\Http\Controllers\Correspondencia;

use App\Http\Controllers\Controller;
use App\Http\Requests\Correspondencia\StoreMemoRequest;
use App\Http\Requests\Correspondencia\UpdateMemoRequest;
use App\Models\Memo;
use App\Services\CorrespondenciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MemoController extends Controller
{
    public function __construct(
        private CorrespondenciaService $correspondenciaService
    ) {}

    /**
     * List memos with optional filters.
     */
    public function index(): View
    {
        $query = Memo::with('user');

        if ($tipo = request()->input('tipo')) {
            $query->byTipo($tipo);
        }
        if ($search = request()->input('search')) {
            $query->search($search);
        }

        $memos = $query->orderBy('fecha', 'desc')->paginate(10);

        return view('memos.index', compact('memos'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        return view('memos.form', ['memo' => null]);
    }

    /**
     * Store a new memo.
     */
    public function store(StoreMemoRequest $request): RedirectResponse
    {
        // Validate folio uniqueness
        $errors = $this->correspondenciaService->validarFolioUnico(
            $request->folio,
            $request->fecha?->year(),
        );
        if ($errors) {
            return back()->withErrors($errors);
        }

        $memo = Memo::create([
            'folio'        => $request->folio,
            'tipo'         => $request->tipo,
            'fecha'        => $request->fecha,
            'destinatario' => $request->destinatario,
            'asunto'       => $request->asunto,
            'user_id'      => auth()->id(),
        ]);

        if ($request->hasFile('archivo')) {
            $memo->archivo = $this->correspondenciaService->subirArchivo($request->file('archivo'), $memo);
            $memo->save();
        }

        return redirect()->route('memos.index')
            ->with('success', 'Memo/Oficio creado correctamente.');
    }

    /**
     * Show a single memo.
     */
    public function show(Memo $memo): View
    {
        $memo->load('user');
        return view('memos.show', compact('memo'));
    }

    /**
     * Show edit form.
     */
    public function edit(Memo $memo): View
    {
        return view('memos.form', compact('memo'));
    }

    /**
     * Update a memo.
     */
    public function update(UpdateMemoRequest $request, Memo $memo): RedirectResponse
    {
        // Validate folio uniqueness excluding current memo
        $errors = $this->correspondenciaService->validarFolioUnico(
            $request->folio,
            $request->fecha?->year(),
            $memo->id,
        );
        if ($errors) {
            return back()->withErrors($errors);
        }

        $memo->update([
            'folio'        => $request->folio,
            'tipo'         => $request->tipo,
            'fecha'        => $request->fecha,
            'destinatario' => $request->destinatario,
            'asunto'       => $request->asunto,
        ]);

        if ($request->hasFile('archivo')) {
            // Delete old file if exists
            $this->correspondenciaService->eliminarArchivo($memo);
            $memo->archivo = $this->correspondenciaService->subirArchivo($request->file('archivo'), $memo);
            $memo->save();
        }

        return redirect()->route('memos.index')
            ->with('success', 'Memo/Oficio actualizado correctamente.');
    }

    /**
     * Delete a memo.
     */
    public function destroy(Memo $memo): RedirectResponse
    {
        $this->correspondenciaService->eliminarArchivo($memo);
        $memo->delete();

        return redirect()->route('memos.index')
            ->with('success', 'Memo/Oficio eliminado correctamente.');
    }

    /**
     * Download the memo's file.
     */
    public function download(Memo $memo): void
    {
        Storage::disk('public')->download($memo->archivo);
    }
}