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
        Schema::create('data_patok', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_patok');
            $table->string('nomor_ruas');
            $table->string('nomor_seksi')->nullable();
            $table->string('nama_ruas');
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('lokasi')->nullable();
            $table->integer('tahun_pemasangan')->nullable();
            $table->decimal('koordinat_x', 15, 8);
            $table->decimal('koordinat_y', 15, 8);
            $table->decimal('koordinat_z', 10, 2)->nullable();
            $table->string('sistem_koordinat')->nullable();
            $table->string('tipe_alat_ukur')->nullable();
            $table->date('tanggal_pengukuran')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_patok');
    }
};
