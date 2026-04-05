<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
    $table->id(); // otomatis jadi id pelanggan
    $table->string('nama_pelanggan');
    $table->string('no_telp', 20);
    $table->text('alamat');
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};