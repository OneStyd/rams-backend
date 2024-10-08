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
        Schema::create('spatial_batas_desa_line', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jalan_tol_id');
            $table->multiLineString('geom');
            $table->string('layer')->nullable();
            $table->timestamps();

            $table->foreign('jalan_tol_id')->references('id')->on('jalan_tol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spatial_batas_desa_line', function (Blueprint $table) {
            $table->dropForeign(['jalan_tol_id']);
        });
        Schema::dropIfExists('spatial_batas_desa_line');
    }
};
