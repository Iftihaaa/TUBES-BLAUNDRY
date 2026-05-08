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
        Schema::table('detail_pemesanan', function (Blueprint $table) {
            $table->string('nama_layanan')->nullable()->after('id_layanan');
            $table->decimal('harga_per_kg', 10, 2)->default(0)->after('nama_layanan');
            $table->decimal('berat_kg', 8, 2)->default(1)->after('harga_per_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_pemesanan', function (Blueprint $table) {
            $table->dropColumn(['nama_layanan', 'harga_per_kg', 'berat_kg']);
        });
    }
};
