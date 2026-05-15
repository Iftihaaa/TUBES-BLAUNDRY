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
        Schema::create('absensis', function (Blueprint $table) {

            $table->id('id_absen');

            $table->unsignedBigInteger('id_pegawai');

            $table->string('kehadiran');

            $table->string('upload_bukti')->nullable();

            $table->timestamps();

            // relasi ke tabel pegawais
            $table->foreign('id_pegawai')
                ->references('id_pegawai')
                ->on('pegawais')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};