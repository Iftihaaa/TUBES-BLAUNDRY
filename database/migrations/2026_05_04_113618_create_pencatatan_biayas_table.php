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
    
    // Fix 1: pakai constrained dengan nama kolom yang benar
    $table->unsignedBigInteger('id_coa'); //Kolom ini dipakai untuk menyimpan ID akun COA.
    $table->foreign('id_coa')->references('id')->on('akuncoa')->cascadeOnDelete();
     //Jika akun COA dihapus, maka pencatatan biaya terkait juga akan dihapus (cascade on delete).
    
    // Fix 2: referensi ke id_pegawai, bukan id
    $table->unsignedBigInteger('id_pegawai');
    $table->foreign('id_pegawai')->references('id_pegawai')->on('pegawais')->cascadeOnDelete();
    
    $table->date('tanggal_catat');
    $table->string('jenis_beban');
    $table->decimal('nominal', 15, 2);
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