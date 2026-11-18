<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['estudiante_nombre', 'estudiante_run', 'apoderado_nombre', 'funcionario_cita', 'fecha', 'hora', 'motivo', 'estado', 'archivo', 'user_id'])]
class Citacion extends Model
{
    use SoftDeletes;

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

    /**
     * Scope: filter by the user who registered the citación (HU-07 emisor).
     */
    public function scopeByEmisor(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: fecha within an inclusive range (HU-07 rango de fechas).
     */
    public function scopeEntreFechas(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        if ($desde) {
            $query->whereDate('fecha', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('fecha', '<=', $hasta);
        }

        return $query;
    }

    /**
     * Human-readable identifier for the audit trail (HU-08).
     */
    public function etiquetaAuditoria(): string
    {
        return "Citación: {$this->estudiante_nombre} ({$this->estudiante_run})";
    }

    /**
     * Reference that identifies the file in search results (HU-07).
     */
    public function referencia(): string
    {
        return "#{$this->id}";
    }

    /**
     * Storage disk holding the attachment.
     */
    public function discoArchivo(): string
    {
        return 'public';
    }
}
