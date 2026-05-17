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
        Schema::create('pencatatan_biaya', function (Blueprint $table) {
            $table->id('id_pencatatan_beban');
            $table->foreignId('id_coa')->constrained('akunCOA')->cascadeOnDelete(); // Relasi ke tabel coa
            $table->foreignId('id_pegawai')->constrained('pegawais')->cascadeOnDelete(); // Relasi ke tabel pegawais
            $table->date('tanggal_catat'); // Tanggal pencatatan
            $table->string('jenis_beban'); // Nama atau jenis bebannya
            $table->decimal('nominal', 15, 2); // Nominal biaya  dengan presisi desimal[cite: 1]
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pencatatan_biaya');
    }
};
