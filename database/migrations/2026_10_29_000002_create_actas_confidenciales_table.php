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
        Schema::create('actas_confidenciales', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->string('categoria', 20);               // 'confidencial' | 'sensible'
            $table->string('ambito', 30);                  // 'conductual' | 'pedagogico' | 'administrativo'
            $table->string('estudiante_nombre', 255);
            $table->string('estudiante_run', 12);
            $table->string('apoderado_nombre', 255);
            $table->date('fecha');                         // Fecha de la entrevista / firma del acta
            $table->string('archivo', 255)->nullable();     // Acta firmada (obligatoria por validación al crear; nullable como en memos)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index('ambito');
            $table->index('estudiante_run');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actas_confidenciales');
    }
};
