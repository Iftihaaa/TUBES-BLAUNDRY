<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analisa_laba_rugi', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');

            $table->decimal('total_pendapatan', 18, 2)->default(0);
            $table->decimal('total_modal', 18, 2)->default(0);
            $table->decimal('total_beban', 18, 2)->default(0);
            $table->decimal('laba_bersih', 18, 2)->default(0);

            $table->string('status_keuangan')->nullable();
            $table->text('ringkasan')->nullable();
            $table->text('analisis_pendapatan')->nullable();
            $table->text('analisis_beban')->nullable();
            $table->text('analisis_margin')->nullable();
            $table->json('rekomendasi')->nullable();
            $table->text('kesimpulan')->nullable();
            $table->longText('raw_response')->nullable();

            $table->timestamps();

            $table->unique(['bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisa_laba_rugi');
    }
};