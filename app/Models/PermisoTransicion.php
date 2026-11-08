<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['permiso_administrativo_id', 'user_id', 'estado_anterior', 'estado_nuevo'])]
class PermisoTransicion extends Model
{
    protected $table = 'permiso_transiciones';

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(PermisoAdministrativo::class, 'permiso_administrativo_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
