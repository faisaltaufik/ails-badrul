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
        Schema::create('workspace_sintak', function (Blueprint $table) {
            $table->id('id_workspace');
            $table->foreignId('id_proyek')
                ->constrained('proyek', 'id_proyek')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_sintak')
                ->constrained('sintak_badrul', 'id_sintak')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('judul_file', 200);
            $table->text('isi_field');
            $table->dateTime('tanggal_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_sintak');
    }
};