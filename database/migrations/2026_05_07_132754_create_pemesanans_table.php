<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemesanans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('layanan_id');

            $table->foreign('layanan_id')
                ->references('id_layanan')
                ->on('layanan')
                ->onDelete('cascade');

            $table->date('tgl_pesan');
            $table->decimal('berat_kg', 8, 2)->default(0);
            $table->decimal('total_harga', 12, 2)->default(0);

            $table->enum('status', [
                'pending',
                'proses',
                'selesai',
                'batal',
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemesanans');
    }
};