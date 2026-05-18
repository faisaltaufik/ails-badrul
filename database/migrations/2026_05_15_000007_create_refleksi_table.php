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
        Schema::create('refleksi', function (Blueprint $table) {
            $table->id('id_refleksi');
            $table->foreignId('id_proyek')
                ->constrained('proyek', 'id_proyek')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->text('isi_refleksi');
            $table->dateTime('tanggal_refleksi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refleksi');
    }
};