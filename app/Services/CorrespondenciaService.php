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
     * Upload the supporting document (respaldo) of a permiso and return
     * the relative path.
     */
    public function subirRespaldoPermiso(UploadedFile $file, PermisoAdministrativo $permiso): string
    {
        $ext = $file->extension();
        $filename = Str::slug("permiso-{$permiso->id}").'.'.$ext;

        return $file->storeAs('permisos', $filename, 'public');
    }

    /**
     * Delete the supporting document (respaldo) of a permiso.
     */
    public function eliminarRespaldoPermiso(PermisoAdministrativo $permiso): void
    {
        if ($permiso->archivo) {
            Storage::disk('public')->delete($permiso->archivo);
        }
    }

    /**
     * Build the next sequential folio for a serie (tipo) and period (year).
     *
     * Format: {TIPO}-{AÑO}-{NNNN}, e.g. MEMO-2026-0001.
     */
    public function siguienteFolio(string $tipo, int $year): string
    {
        $siguiente = 1;

        Memo::where('tipo', $tipo)
            ->whereYear('fecha', $year)
            ->pluck('folio')
            ->each(function (string $folio) use (&$siguiente) {
                if (preg_match('/(\d+)$/', $folio, $matches)) {
                    $siguiente = max($siguiente, ((int) $matches[1]) + 1);
                }
            });

        return sprintf('%s-%d-%04d', strtoupper($tipo), $year, $siguiente);
    }

    /**
     * Validate that a folio is unique within its serie and period.
     */
    public function validarFolioUnico(string $folio, string $tipo, int $year, ?int $excludeId = null): array
    {
        if (! Memo::folioUnicoEnSerie($folio, $tipo, $year, $excludeId)) {
            return ['folio' => "El folio '{$folio}' ya existe en la serie '{$tipo}' del año {$year}."];
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
