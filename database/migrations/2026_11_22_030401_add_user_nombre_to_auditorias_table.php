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
        Schema::table('auditorias', function (Blueprint $table) {
            // Instantánea del actor en el momento del evento (QA-F4-007).
            $table->string('user_nombre')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropColumn('user_nombre');
        });
    }
};
