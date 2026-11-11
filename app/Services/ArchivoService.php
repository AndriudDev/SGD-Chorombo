<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArchivoService
{
    /**
     * Upload a file to the given disk under the given folder
     * and return the relative storage path.
     */
    public function subir(UploadedFile $file, string $carpeta, string $nombreBase, string $disco = 'public'): string
    {
        $ext = $file->extension();
        $filename = Str::slug($nombreBase).'.'.$ext;

        return $file->storeAs($carpeta, $filename, $disco);
    }

    /**
     * Delete a file from the given disk by relative path.
     */
    public function eliminar(?string $ruta, string $disco = 'public'): void
    {
        if ($ruta) {
            Storage::disk($disco)->delete($ruta);
        }
    }
}
