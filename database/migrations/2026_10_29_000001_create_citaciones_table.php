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
        Schema::create('citaciones', function (Blueprint $table) {
            $table->id();
            $table->string('estudiante_nombre', 255);
            $table->string('estudiante_run', 12);
            $table->string('apoderado_nombre', 255);
            $table->string('funcionario_cita', 255);       // Funcionario que cita (texto libre del formulario)
            $table->date('fecha');
            $table->string('hora', 5);                     // 'HH:MM' 24h (date_format:H:i en validación)
            $table->string('motivo', 20);                  // 'conductual' | 'pedagogico' | 'administrativo'
            $table->string('estado', 30)->default('programada'); // 'programada' | 'asistio' | 'no_asistio' | 'reprogramada'
            $table->string('archivo', 255)->nullable();    // Copia de la citación firmada o colilla de recepción
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index('estado');
            $table->index('fecha');
            $table->index('estudiante_run');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citaciones');
    }
};
