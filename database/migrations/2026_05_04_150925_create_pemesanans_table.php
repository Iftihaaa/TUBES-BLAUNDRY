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
        Schema::create('pemesanan', function (Blueprint $table) {
            $table->id('id_pemesanan');
            $table->foreignId('id_pelanggan')->constrained('members')->onDelete('cascade');
            $table->foreignId('id_layanan')->constrained('layanan', 'id_layanan')->onDelete('cascade');
            $table->date('tgl_pesan');
            $table->enum('status', ['on process', 'done'])->default('on process');
            $table->decimal('berat_kg', 8, 2);
            $table->decimal('total_harga', 10, 2);
            $table->enum('pengantaran', ['pick up', 'delivery'])->default('pick up');
            $table->decimal('ongkir', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemesanan');
    }
};
