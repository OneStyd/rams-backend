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
        Schema::create('spatial_saluran_line', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jalan_tol_id');
            $table->multiPolygon('geom');
            $table->string('layer')->nullable();
            $table->string('jenis_material')->nullable();
            $table->string('kondisi')->nullable();
            $table->string('panjang')->nullable();
            $table->string('lebar')->nullable();
            $table->string('tinggi')->nullable();
            $table->timestamps();

            $table->foreign('jalan_tol_id')->references('id')->on('jalan_tol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spatial_saluran_line', function (Blueprint $table) {
            $table->dropForeign(['jalan_tol_id']);
        });
        Schema::dropIfExists('spatial_saluran_line');
    }
};
