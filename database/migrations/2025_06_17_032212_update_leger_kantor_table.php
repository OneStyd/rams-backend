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
        Schema::table('leger_kantor', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['data_kantor_identifikasi_id']);
            $table->dropForeign(['data_kantor_teknik1_id']);
            $table->dropForeign(['data_kantor_luas_lahan_id']);
            $table->dropForeign(['data_kantor_teknik2_id']);
            $table->dropForeign(['data_kantor_realisasi_id']);

            // Drop columns
            $table->dropColumn([
                'data_kantor_identifikasi_id',
                'data_kantor_teknik1_id',
                'data_kantor_luas_lahan_id',
                'data_kantor_teknik2_id',
                'data_kantor_realisasi_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leger_kantor', function (Blueprint $table) {
            $table->unsignedBigInteger('data_kantor_identifikasi_id')->nullable();
            $table->unsignedBigInteger('data_kantor_teknik1_id')->nullable();
            $table->unsignedBigInteger('data_kantor_luas_lahan_id')->nullable();
            $table->unsignedBigInteger('data_kantor_teknik2_id')->nullable();
            $table->unsignedBigInteger('data_kantor_realisasi_id')->nullable();

            $table->foreign('data_kantor_identifikasi_id')->references('id')->on('data_kantor_identifikasi');
            $table->foreign('data_kantor_teknik1_id')->references('id')->on('data_kantor_teknik1');
            $table->foreign('data_kantor_luas_lahan_id')->references('id')->on('data_kantor_luas_lahan');
            $table->foreign('data_kantor_teknik2_id')->references('id')->on('data_kantor_teknik2');
            $table->foreign('data_kantor_realisasi_id')->references('id')->on('data_kantor_realisasi');
        });
    }
};
