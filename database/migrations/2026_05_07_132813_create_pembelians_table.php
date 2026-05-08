<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelians', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();

            $table->date('tgl_beli');
            $table->string('jenis_pembelian');
            $table->text('keterangan')->nullable();
            $table->decimal('total_harga', 12, 2)->default(0);

            $table->enum('status', [
                'pending',
                'selesai',
                'batal',
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembelians');
    }
};