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
        Schema::create('penggajian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pegawai')
            ->constrained('pegawais');
            $table->foreignId('kode_akun')
                ->nullable()
                ->constrained('akuncoa');
            $table->integer('jumlah_hadir')->default(0);
            $table->integer('jumlah_sakit')->default(0);
            $table->integer('jumlah_izin')->default(0);
            $table->integer('jumlah_alpa')->default(0);
            $table->double('gaji_pokok')->default(0);
            $table->double('potongan')->default(0);
            $table->string('bonus')->default('tidak');
            $table->double('total_gaji')->default(0);
            $table->string('metode_pembayaran')
                ->nullable();
            $table->string('status_pembayaran')
                ->default('belum dibayar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggajian');
    }
};
