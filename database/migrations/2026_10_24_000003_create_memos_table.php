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
        Schema::create('memos', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50);
            $table->string('tipo', 20);  // 'memo' | 'oficio'
            $table->date('fecha');
            $table->string('destinatario', 255);
            $table->text('asunto');
            $table->string('archivo', 255)->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index('folio');  // Folio único por año validado en app (CorrespondenciaService)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memos');
    }
};