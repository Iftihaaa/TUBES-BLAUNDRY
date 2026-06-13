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
        Schema::create('email_pemesanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pemesanan');
            $table->foreign('id_pemesanan')
                ->references('id_pemesanan')
                ->on('pemesanan')
                ->onDelete('cascade');
            $table->string('jenis_email'); // 'invoice' atau 'selesai'
            $table->string('status')->nullable();
            $table->dateTime('tgl_pengiriman_pesan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_pemesanan');
    }
};
