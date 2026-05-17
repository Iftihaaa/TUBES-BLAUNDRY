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
        Schema::create('pengiriman_email', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pencatatan_beban')->constrained('pencatatan_biaya', 'id_pencatatan_beban')->onDelete('cascade');
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
        Schema::dropIfExists('pengiriman_email');
    }
};