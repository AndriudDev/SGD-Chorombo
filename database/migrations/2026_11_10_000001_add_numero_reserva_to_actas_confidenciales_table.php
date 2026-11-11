<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Numeración reservada asignada por Dirección (HU-05).
     */
    public function up(): void
    {
        Schema::table('actas_confidenciales', function (Blueprint $table) {
            $table->string('numero_reserva', 50)->nullable()->after('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actas_confidenciales', function (Blueprint $table) {
            $table->dropColumn('numero_reserva');
        });
    }
};
