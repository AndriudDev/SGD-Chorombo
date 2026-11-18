<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['titulo', 'categoria', 'fecha', 'descripcion', 'archivo', 'user_id'])]
class ReunionComunal extends Model
{
    use SoftDeletes;

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

    /**
     * Scope: filter by the user who registered the reunión (HU-07 emisor).
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
        return $this->titulo;
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
