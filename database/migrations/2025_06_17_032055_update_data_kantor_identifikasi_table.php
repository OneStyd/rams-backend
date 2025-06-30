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
        Schema::table('data_kantor_identifikasi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_leger_kantor')->nullable()->after('id');
            $table->foreign('id_leger_kantor')->references('id')->on('leger_kantor');

            // Add the two new columns
            $table->string('tanggal_dibuka')->nullable()->after('tanggal_selesai_bangun');
            $table->string('tanggal_ditutup')->nullable()->after('tanggal_dibuka');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_kantor_identifikasi', function (Blueprint $table) {
            $table->dropForeign(['id_leger_kantor']);
            $table->dropColumn('id_leger_kantor');
            $table->dropColumn('tanggal_dibuka');
            $table->dropColumn('tanggal_ditutup');
        });
    }
};
