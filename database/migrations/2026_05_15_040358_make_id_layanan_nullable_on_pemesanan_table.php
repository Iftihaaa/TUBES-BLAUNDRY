<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pemesanan', 'id_layanan')) {
            Schema::table('pemesanan', function (Blueprint $table) {
                $table->unsignedBigInteger('id_layanan')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pemesanan', 'id_layanan')) {
            Schema::table('pemesanan', function (Blueprint $table) {
                $table->unsignedBigInteger('id_layanan')->nullable(false)->change();
            });
        }
    }
};