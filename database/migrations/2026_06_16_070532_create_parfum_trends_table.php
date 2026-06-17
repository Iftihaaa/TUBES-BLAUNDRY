<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parfum_trends', function (Blueprint $table) {
            $table->id();
            $table->string('nama_tren');
            $table->text('analisis_ai');
            $table->json('parfum_populer')->nullable();
            $table->string('aroma_terpopuler')->nullable();
            $table->string('rekomendasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parfum_trends');
    }
};