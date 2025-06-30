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
        Schema::create('leger_patok', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leger_id');
            $table->string('kode_leger', 10);
            $table->unsignedBigInteger('data_patok_id');
            $table->timestamps();

            $table->foreign('leger_id')->references('id')->on('leger')->onDelete('cascade');
            $table->foreign('data_patok_id')->references('id')->on('data_patok')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leger_patok', function (Blueprint $table) {
            $table->dropForeign(['leger_id']);
            $table->dropForeign(['data_patok_id']);
        });
        Schema::dropIfExists('leger_patok');
    }
};
