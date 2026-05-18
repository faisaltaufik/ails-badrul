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
        Schema::create('sintak_badrul', function (Blueprint $table) {
            $table->id('id_sintak');
            $table->string('kode_sintak', 2);
            $table->string('nama_sintak', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sintak_badrul');
    }
};