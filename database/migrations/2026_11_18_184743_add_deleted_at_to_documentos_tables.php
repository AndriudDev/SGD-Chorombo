<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add soft deletes to the five document tables (HU-08: papelera recuperable).
     */
    public function up(): void
    {
        foreach (['memos', 'permisos_administrativos', 'citaciones', 'actas_confidenciales', 'reuniones_comunales'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['memos', 'permisos_administrativos', 'citaciones', 'actas_confidenciales', 'reuniones_comunales'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
