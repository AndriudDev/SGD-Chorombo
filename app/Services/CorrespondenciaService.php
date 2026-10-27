<?php

namespace App\Services;

use App\Models\Memo;
use App\Models\PermisoAdministrativo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CorrespondenciaService
{
    /**
     * Upload a file for a memo and return the relative path.
     */
    public function subirArchivo(UploadedFile $file, Memo $memo): string
    {
        $ext = $file->extension();
        $filename = Str::slug("{$memo->folio}-{$memo->tipo}-{$memo->id}").'.'.$ext;
        $path = $file->storeAs('memos', $filename, 'public');

        return $path;
    }

    /**
     * Delete the associated file of a memo.
     */
    public function eliminarArchivo(Memo $memo): void
    {
        if ($memo->archivo) {
            Storage::disk('public')->delete($memo->archivo);
        }
    }

    /**
     * Validate that a folio is unique in a given year.
     */
    public function validarFolioUnico(string $folio, ?int $year = null, ?int $excludeId = null): array
    {
        $year = $year ?? date('Y');
        if (! Memo::folioUnicoEnAnio($folio, $year, $excludeId)) {
            return ['folio' => "El folio '{$folio}' ya existe en el año {$year}."];
        }

        return [];
    }

    /**
     * Calculate total accumulated days for a user in a year.
     */
    public function calcularDiasAcumulados(int $userId, int $year): float
    {
        return PermisoAdministrativo::contadorDiasAcumulados($userId, $year);
    }
}
