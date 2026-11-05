<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['folio', 'tipo', 'fecha', 'destinatario', 'asunto', 'archivo', 'user_id'])]
class Memo extends Model
{
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
     * Scope: filter by year.
     */
    public function scopeByYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('fecha', $year);
    }

    /**
     * Scope: filter by tipo (memo|oficio).
     */
    public function scopeByTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope: search in folio, destinatario, asunto.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('folio', 'LIKE', "%{$search}%")
                ->orWhere('destinatario', 'LIKE', "%{$search}%")
                ->orWhere('asunto', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope: filter by exact fecha.
     */
    public function scopeByFecha(Builder $query, string $fecha): Builder
    {
        return $query->whereDate('fecha', $fecha);
    }

    /**
     * Check if a folio is unique within its serie (tipo) and year.
     */
    public static function folioUnicoEnSerie(string $folio, string $tipo, int $year, ?int $excludeId = null): bool
    {
        $q = self::where('folio', $folio)->where('tipo', $tipo)->whereYear('fecha', $year);
        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return ! $q->exists();
    }
}
