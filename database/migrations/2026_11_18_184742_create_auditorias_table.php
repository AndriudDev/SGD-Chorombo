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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('accion', 30);      // crear | editar | descargar | eliminar | restaurar | eliminar_definitivo
            $table->string('modelo_type', 100); // clase del documento (morph)
            $table->unsignedBigInteger('modelo_id');
            $table->string('modelo_nombre')->nullable(); // instantánea del identificador del documento
            $table->json('detalles')->nullable();        // cambios de campos: {campo: [anterior, nuevo]}
            $table->timestamp('created_at')->useCurrent();

            $table->index(['modelo_type', 'modelo_id']);
            $table->index('accion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
