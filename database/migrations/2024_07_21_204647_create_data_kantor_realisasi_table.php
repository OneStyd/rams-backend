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
        Schema::create('data_kantor_realisasi', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun');
            $table->string('kegiatan');
            $table->string('penyedia_jasa')->nullable();
            $table->string('cacah')->nullable();
            $table->string('biaya')->nullable();
            $table->string('sumber_dana')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_kantor_realisasi');
    }
};