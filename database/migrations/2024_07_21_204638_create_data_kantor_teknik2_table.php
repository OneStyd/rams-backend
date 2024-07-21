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
        Schema::create('data_kantor_teknik2', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun');
            $table->string('uraian');
            $table->string('jumlah')->nullable();
            $table->string('panjang')->nullable();
            $table->string('kondisi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_kantor_teknik2');
    }
};
