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
        Schema::table('data_jembatan_teknik2_bangunanbawah', function (Blueprint $table) {
            $table->unsignedBigInteger('id_leger_jalan')->nullable()->after('id');
            $table->foreign('id_leger_jalan')->references('id')->on('leger_jalan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_jembatan_teknik2_bangunanbawah', function (Blueprint $table) {
            $table->dropForeign(['id_leger_jalan']);
            $table->dropColumn('id_leger_jalan');
        });
    }
};