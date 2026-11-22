<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;

class AuditoriaService
{
    /**
     * Registrar una acción sobre un documento en la bitácora (HU-08).
     *
     * @param  array<string, array{0: mixed, 1: mixed}>|null  $detalles  cambios de campos: [campo => [anterior, nuevo]]
     */
    public function registrar(string $accion, Model $modelo, ?array $detalles = null): void
    {
        Auditoria::create([
            'user_id' => auth()->id(),
            'user_nombre' => auth()->user()?->name,
            'accion' => $accion,
            'modelo_type' => $modelo->getMorphClass(),
            'modelo_id' => $modelo->getKey(),
            'modelo_nombre' => $this->etiqueta($modelo),
            'detalles' => $detalles ?: null,
        ]);
    }

    /**
     * Identificador legible del documento para la bitácora.
     */
    private function etiqueta(Model $modelo): ?string
    {
        if (method_exists($modelo, 'etiquetaAuditoria')) {
            return $modelo->etiquetaAuditoria();
        }

        return (string) $modelo->getKey();
    }
}
