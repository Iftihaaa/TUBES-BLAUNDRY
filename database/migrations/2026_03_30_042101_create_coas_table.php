<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akun_coas', function (Blueprint $table) {
            $table->id();
            $table->string('header_akun');
            $table->string('kode_akun');
            $table->string('nama_akun');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akun_coas');
    }
};