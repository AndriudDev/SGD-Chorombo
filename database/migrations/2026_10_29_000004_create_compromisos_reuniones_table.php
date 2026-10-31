<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compromisos_reuniones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reunion_id')->constrained('reuniones_comunales')->cascadeOnDelete();
            $table->string('descripcion', 500);
            $table->date('fecha_limite');                  // Fecha límite de entrega institucional
            $table->timestamps();

            $table->index('fecha_limite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compromisos_reuniones');
    }
};
