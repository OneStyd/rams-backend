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
        Schema::table('data_kantor_teknik1', function (Blueprint $table) {
            $table->unsignedBigInteger('id_leger_kantor')->nullable()->after('id');
            $table->foreign('id_leger_kantor')->references('id')->on('leger_kantor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_kantor_teknik1', function (Blueprint $table) {
            $table->dropForeign(['id_leger_kantor']);
            $table->dropColumn('id_leger_kantor');
        });
    }
};
