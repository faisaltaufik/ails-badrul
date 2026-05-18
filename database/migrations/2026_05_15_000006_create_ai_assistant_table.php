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
        Schema::create('ai_assistant', function (Blueprint $table) {
            $table->id('id_ai');
            $table->foreignId('id_sintak')
                ->constrained('sintak_badrul', 'id_sintak')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('nama_ai', 150);
            $table->text('deskripsi_ai');
            $table->text('prompt_otomatis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_assistant');
    }
};