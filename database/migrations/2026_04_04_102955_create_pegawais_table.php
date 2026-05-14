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
    Schema::create('pegawais', function (Blueprint $table) {
        $table->id('id_pegawai');
        $table->string('nama');
        $table->string('jabatan');
        $table->string('no_telp');
        $table->text('alamat');
        $table->decimal('gaji_pokok', 15, 2)->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
