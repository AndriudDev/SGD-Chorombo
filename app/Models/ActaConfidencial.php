<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['titulo', 'categoria', 'ambito', 'estudiante_nombre', 'estudiante_run', 'apoderado_nombre', 'fecha', 'numero_reserva', 'archivo', 'user_id'])]
class ActaConfidencial extends Model
{
    use SoftDeletes;

    /** Clasificación de resguardo del documento (HU-05). */
    public const CATEGORIAS = [
        'confidencial' => 'Confidencial',
        'sensible' => 'Sensible',
    ];

    /** Ámbito del acuerdo (clasificación del contenido, HU-05). */
    public const AMBITOS = [
        'conductual' => 'Conductual',
        'pedagogico' => 'Pedagógico',
        'administrativo' => 'Administrativo',
    ];

    protected $table = 'actas_confidenciales';

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
     * Scope: filter by ámbito.
     */
    public function scopeByAmbito(Builder $query, string $ambito): Builder
    {
        return $query->where('ambito', $ambito);
    }

    /**
     * Scope: filter by categoría de resguardo.
     */
    public function scopeByCategoria(Builder $query, string $categoria): Builder
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope: search by student name/RUN or título.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('estudiante_nombre', 'LIKE', "%{$search}%")
                ->orWhere('estudiante_run', 'LIKE', "%{$search}%")
                ->orWhere('titulo', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope: filter by the user who registered the acta (HU-07 emisor).
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
        $etiqueta = "Acta: {$this->titulo}";

        return $this->numero_reserva ? "{$etiqueta} (Res. {$this->numero_reserva})" : $etiqueta;
    }

    /**
     * Reference that identifies the file in search results (HU-07).
     */
    public function referencia(): string
    {
        return $this->numero_reserva ?: "#{$this->id}";
    }

    /**
     * Storage disk holding the attachment (HU-05: disco privado).
     */
    public function discoArchivo(): string
    {
        return 'local';
    }
}
