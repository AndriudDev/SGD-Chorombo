<?php

namespace App\Observers;

use App\Models\Auditoria;
use App\Services\AuditoriaService;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra automáticamente cargas, ediciones y eliminaciones de los
 * documentos en la bitácora (HU-08). Las descargas se registran
 * explícitamente desde los controladores al servir el archivo.
 */
class AuditoriaObserver
{
    public function __construct(
        private AuditoriaService $auditoria
    ) {}

    public function created(Model $modelo): void
    {
        $this->auditoria->registrar(Auditoria::ACCION_CREAR, $modelo);
    }

    public function updated(Model $modelo): void
    {
        // QA-F4-006: restaurar solo mueve deleted_at; no es una edición del
        // contenido y el evento restored ya registra la acción de restauración.
        $cambios = collect($modelo->getChanges())
            ->except(['created_at', 'updated_at', 'archivo', 'deleted_at']);

        // Guardar únicamente el adjunto no es una edición del registro.
        if ($cambios->isEmpty()) {
            return;
        }

        $detalles = [];
        foreach ($cambios as $campo => $valor) {
            $detalles[$campo] = [$modelo->getOriginal($campo), $valor];
        }

        $this->auditoria->registrar(Auditoria::ACCION_EDITAR, $modelo, $detalles);
    }

    public function deleted(Model $modelo): void
    {
        // La purga definitiva emite también este evento; forceDeleted registra esa acción.
        if (method_exists($modelo, 'isForceDeleting') && $modelo->isForceDeleting()) {
            return;
        }

        $this->auditoria->registrar(Auditoria::ACCION_ELIMINAR, $modelo);
    }

    public function restored(Model $modelo): void
    {
        $this->auditoria->registrar(Auditoria::ACCION_RESTAURAR, $modelo);
    }

    public function forceDeleted(Model $modelo): void
    {
        $this->auditoria->registrar(Auditoria::ACCION_ELIMINAR_DEFINITIVO, $modelo);
    }
}
