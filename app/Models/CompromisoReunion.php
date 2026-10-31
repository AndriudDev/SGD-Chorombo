<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reunion_id', 'descripcion', 'fecha_limite'])]
class CompromisoReunion extends Model
{
    protected $table = 'compromisos_reuniones';

    protected function casts(): array
    {
        return [
            'fecha_limite' => 'date',
        ];
    }

    public function reunion(): BelongsTo
    {
        return $this->belongsTo(ReunionComunal::class, 'reunion_id');
    }
}
