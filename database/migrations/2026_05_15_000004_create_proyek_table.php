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
        Schema::create('proyek', function (Blueprint $table) {
            $table->id('id_proyek');
            $table->foreignId('id_user')
                ->constrained('users', 'id_user')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->integer('pertemuan_ke');
            $table->string('materi', 200);
            $table->string('nama_proyek', 200);
            $table->text('deskripsi');
            $table->dateTime('tanggal_buat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proyek');
    }
};