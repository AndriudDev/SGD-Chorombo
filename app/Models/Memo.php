<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['folio', 'tipo', 'fecha', 'destinatario', 'asunto', 'archivo', 'user_id'])]
class Memo extends Model
{
    use SoftDeletes;

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
     * Scope: filter by the user who registered the document (HU-07 emisor).
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
        return $this->folio;
    }

    /**
     * Reference that identifies the file in search results (HU-07).
     */
    public function referencia(): string
    {
        return $this->folio;
    }

    /**
     * Storage disk holding the attachment.
     */
    public function discoArchivo(): string
    {
        return 'public';
    }

    /**
     * Check if a folio is unique within its serie (tipo) and year.
     * Includes trashed documents so a folio is never reused while its
     * holder can still be restored from the papelera (HU-08).
     */
    public static function folioUnicoEnSerie(string $folio, string $tipo, int $year, ?int $excludeId = null): bool
    {
        $q = self::withTrashed()->where('folio', $folio)->where('tipo', $tipo)->whereYear('fecha', $year);
        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return ! $q->exists();
    }
}
