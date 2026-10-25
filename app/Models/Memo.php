<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['folio', 'tipo', 'fecha', 'destinatario', 'asunto', 'archivo', 'user_id'])]
class Memo extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope: filter by year.
     */
    public static function byYear(int $year)
    {
        return self::whereYear('fecha', $year);
    }

    /**
     * Scope: filter by tipo (memo|oficio).
     */
    public static function byTipo(string $tipo)
    {
        return self::where('tipo', $tipo);
    }

    /**
     * Scope: search in folio, destinatario, asunto.
     */
    public static function search(string $query)
    {
        return self::where(function ($q) {
            $q->where('folio', 'LIKE', "%{$query}%")
              ->orWhere('destinatario', 'LIKE', "%{$query}%")
              ->orWhere('asunto', 'LIKE', "%{$query}%");
        });
    }

    /**
     * Check if a folio is unique in a given year.
     */
    public static function folioUnicoEnAnio(string $folio, int $year, ?int $excludeId = null): bool
    {
        $q = self::where('folio', $folio)->whereYear('fecha', $year);
        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }
        return !$q->exists();
    }
}