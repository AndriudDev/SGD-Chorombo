<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'accion', 'modelo_type', 'modelo_id', 'modelo_nombre', 'detalles', 'created_at'])]
class Auditoria extends Model
{
    /** Acciones registradas en la bitácora (HU-08). */
    public const ACCION_CREAR = 'crear';

    public const ACCION_EDITAR = 'editar';

    public const ACCION_DESCARGAR = 'descargar';

    public const ACCION_ELIMINAR = 'eliminar';

    public const ACCION_RESTAURAR = 'restaurar';

    public const ACCION_ELIMINAR_DEFINITIVO = 'eliminar_definitivo';

    /** @var array<string, string> etiquetas legibles de las acciones */
    public const ACCIONES = [
        self::ACCION_CREAR => 'Carga',
        self::ACCION_EDITAR => 'Edición',
        self::ACCION_DESCARGAR => 'Descarga',
        self::ACCION_ELIMINAR => 'Eliminación',
        self::ACCION_RESTAURAR => 'Restauración',
        self::ACCION_ELIMINAR_DEFINITIVO => 'Eliminación definitiva',
    ];

    /** Solo se registra fecha/hora de creación (registro bitácora). */
    public const UPDATED_AT = null;

    protected $table = 'auditorias';

    protected function casts(): array
    {
        return [
            'detalles' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: filter by user who performed the action.
     */
    public function scopeByUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: filter by action.
     */
    public function scopeByAccion(Builder $query, string $accion): Builder
    {
        return $query->where('accion', $accion);
    }

    /**
     * Scope: filter created_at between two dates (inclusive).
     */
    public function scopeEntreFechas(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        if ($desde) {
            $query->whereDate('created_at', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('created_at', '<=', $hasta);
        }

        return $query;
    }

    /**
     * Scope: only rows whose model belongs to the given classes (permission filter).
     *
     * @param  array<int, string>  $clases
     */
    public function scopeDeModelos(Builder $query, array $clases): Builder
    {
        return $query->whereIn('modelo_type', $clases);
    }
}