<?php

namespace App\Providers;

use App\Models\ActaConfidencial;
use App\Models\Citacion;
use App\Models\CompromisoReunion;
use App\Models\Memo;
use App\Models\PermisoAdministrativo;
use App\Models\ReunionComunal;
use App\Observers\AuditoriaObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bitácora de auditoría sobre todos los documentos (HU-08)
        foreach ([Memo::class, PermisoAdministrativo::class, Citacion::class, ActaConfidencial::class, ReunionComunal::class, CompromisoReunion::class] as $modelo) {
            $modelo::observe(AuditoriaObserver::class);
        }
    }
}
