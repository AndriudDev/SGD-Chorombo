<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['titulo', 'categoria', 'ambito', 'estudiante_nombre', 'estudiante_run', 'apoderado_nombre', 'fecha', 'archivo', 'user_id'])]
class ActaConfidencial extends Model
{
    /** Clasificación de resguardo del documento (HU-05). */
    public const CATEGORIAS = [
        'confidencial' => 'Confidencial',
        'sensible' => 'Sensible',
    ];

    /** Ámbito del acuerdo; UTP solo consulta las de ámbito pedagógico (HU-05). */
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
}
