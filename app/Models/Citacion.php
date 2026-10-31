<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['estudiante_nombre', 'estudiante_run', 'apoderado_nombre', 'funcionario_cita', 'fecha', 'hora', 'motivo', 'estado', 'archivo', 'user_id'])]
class Citacion extends Model
{
    /** Motivos de citación (HU-04). */
    public const MOTIVOS = [
        'conductual' => 'Conductual',
        'pedagogico' => 'Pedagógico',
        'administrativo' => 'Administrativo',
    ];

    /** Estados de seguimiento de la citación (HU-04). */
    public const ESTADOS = [
        'programada' => 'Programada',
        'asistio' => 'Asistió',
        'no_asistio' => 'No asistió / Justificada',
        'reprogramada' => 'Reprogramada',
    ];

    protected $table = 'citaciones';

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope: filter by estado.
     */
    public function scopeByEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope: filter by motivo.
     */
    public function scopeByMotivo(Builder $query, string $motivo): Builder
    {
        return $query->where('motivo', $motivo);
    }

    /**
     * Scope: search in student name/RUN and apoderado.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('estudiante_nombre', 'LIKE', "%{$search}%")
                ->orWhere('estudiante_run', 'LIKE', "%{$search}%")
                ->orWhere('apoderado_nombre', 'LIKE', "%{$search}%");
        });
    }
}
