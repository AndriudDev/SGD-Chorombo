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
        Schema::create('reuniones_comunales', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->string('categoria', 40);               // 'red_utp' | 'reunion_directores' | 'mesa_convivencia' | 'daem_slep'
            $table->date('fecha');
            $table->text('descripcion')->nullable();
            $table->string('archivo', 255)->nullable();     // Minuta, orientación o presentación (obligatoria por validación al crear)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index('categoria');
            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reuniones_comunales');
    }
};
