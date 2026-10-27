<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'fecha_inicio', 'fecha_fin', 'tipo_jornada', 'motivo', 'estado', 'aprobado_por', 'created_by'])]
class PermisoAdministrativo extends Model
{
    protected $table = 'permisos_administrativos';

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate number of days requested (inclusive).
     * A media jornada counts as half a day per date.
     */
    public function diasSolicitados(): float
    {
        $days = $this->fecha_inicio->diffInDays($this->fecha_fin) + 1;

        return $this->tipo_jornada === 'media' ? $days * 0.5 : (float) $days;
    }

    /**
     * Count total approved days for a user in a given year.
     */
    public static function contadorDiasAcumulados(int $userId, int $year): float
    {
        return self::where('user_id', $userId)
            ->where('estado', 'aprobado')
            ->whereYear('fecha_inicio', $year)
            ->get()
            ->sum(fn ($p) => $p->diasSolicitados());
    }

    /**
     * Scope: only pending requests.
     */
    public static function pendientes()
    {
        return self::where('estado', 'pendiente');
    }

    /**
     * Scope: filter by estado.
     */
    public static function byEstado(string $estado)
    {
        return self::where('estado', $estado);
    }
}
