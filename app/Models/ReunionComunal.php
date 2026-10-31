<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['titulo', 'categoria', 'fecha', 'descripcion', 'archivo', 'user_id'])]
class ReunionComunal extends Model
{
    /** Categorías de encuentro comunal (HU-06). */
    public const CATEGORIAS = [
        'red_utp' => 'Red de UTP',
        'reunion_directores' => 'Reunión de Directores',
        'mesa_convivencia' => 'Mesa Comunal de Convivencia',
        'daem_slep' => 'DAEM/SLEP General',
    ];

    protected $table = 'reuniones_comunales';

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

    public function compromisos(): HasMany
    {
        return $this->hasMany(CompromisoReunion::class, 'reunion_id');
    }

    /**
     * Scope: filter by categoría.
     */
    public function scopeByCategoria(Builder $query, string $categoria): Builder
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope: search in título and descripción.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('titulo', 'LIKE', "%{$search}%")
                ->orWhere('descripcion', 'LIKE', "%{$search}%");
        });
    }
}
