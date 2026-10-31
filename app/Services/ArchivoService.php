<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArchivoService
{
    /**
     * Upload a file to the public disk under the given folder
     * and return the relative storage path.
     */
    public function subir(UploadedFile $file, string $carpeta, string $nombreBase): string
    {
        $ext = $file->extension();
        $filename = Str::slug($nombreBase).'.'.$ext;

        return $file->storeAs($carpeta, $filename, 'public');
    }

    /**
     * Delete a file from the public disk by relative path.
     */
    public function eliminar(?string $ruta): void
    {
        if ($ruta) {
            Storage::disk('public')->delete($ruta);
        }
    }
}
