<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'fecha_inicio', 'fecha_fin', 'tipo_jornada', 'motivo', 'archivo', 'estado', 'aprobado_por', 'created_by'])]
class PermisoAdministrativo extends Model
{
    use SoftDeletes;
    /**
     * Annual day quota per funcionario. Provisional value while the
     * definitive quota is confirmed with Dirección (HU-03).
     */
    public const CUPO_ANUAL = 10;

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_RECHAZADO = 'rechazado';

    public const ESTADO_REVOCADA = 'revocada';

    /**
     * Legal state transitions (REV-03). An approved request can only leave
     * its state through the formal revocation flow, never through rejection.
     */
    private const TRANSICIONES = [
        'pendiente' => ['aprobado', 'rechazado'],
        'aprobado' => ['revocada'],
        'rechazado' => [],
        'revocada' => [],
    ];

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
     * Audit trail of state changes (REV-03). The user_id of each row is the
     * person who performed that specific transition.
     */
    public function transiciones(): HasMany
    {
        return $this->hasMany(PermisoTransicion::class, 'permiso_administrativo_id');
    }

    public function ultimaTransicion(): HasOne
    {
        return $this->hasOne(PermisoTransicion::class, 'permiso_administrativo_id')->latestOfMany();
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
     * Whether this request is allowed to move to the given state.
     */
    public function puedePasarA(string $nuevoEstado): bool
    {
        return in_array($nuevoEstado, self::TRANSICIONES[$this->estado] ?? [], true);
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

    /**
     * Scope: search in motivo and funcionario name (HU-07).
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('motivo', 'LIKE', "%{$search}%")
                ->orWhereHas('user', fn (Builder $usuario) => $usuario->where('name', 'LIKE', "%{$search}%"));
        });
    }

    /**
     * Scope: filter by the user who registered the solicitud (HU-07 emisor).
     */
    public function scopeByEmisor(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope: fecha_inicio within an inclusive range (HU-07 rango de fechas).
     */
    public function scopeEntreFechas(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        if ($desde) {
            $query->whereDate('fecha_inicio', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('fecha_inicio', '<=', $hasta);
        }

        return $query;
    }

    /**
     * Human-readable identifier for the audit trail (HU-08).
     */
    public function etiquetaAuditoria(): string
    {
        return "Permiso: {$this->motivo} ({$this->fecha_inicio->format('d/m/Y')} al {$this->fecha_fin->format('d/m/Y')})";
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
