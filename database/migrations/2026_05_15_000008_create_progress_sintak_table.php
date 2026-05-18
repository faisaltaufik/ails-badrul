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
        Schema::create('progress_sintak', function (Blueprint $table) {
            $table->id('id_progress');
            $table->foreignId('id_proyek')
                ->constrained('proyek', 'id_proyek')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_sintak')
                ->constrained('sintak_badrul', 'id_sintak')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('status', 20);
            $table->dateTime('terakhir_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_sintak');
    }
};