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
        Schema::table('data_gerbang_realisasi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_leger_gerbang')->nullable()->after('id');
            $table->foreign('id_leger_gerbang')->references('id')->on('leger_gerbang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_gerbang_realisasi', function (Blueprint $table) {
            $table->dropForeign(['id_leger_gerbang']);
            $table->dropColumn('id_leger_gerbang');
        });
    }
};
