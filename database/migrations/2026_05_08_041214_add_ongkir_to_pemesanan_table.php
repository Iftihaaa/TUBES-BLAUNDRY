<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pemesanan', 'ongkir')) {
            Schema::table('pemesanan', function (Blueprint $table) {
                $table->decimal('ongkir', 10, 2)
                    ->default(0)
                    ->after('total_harga');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pemesanan', 'ongkir')) {
            Schema::table('pemesanan', function (Blueprint $table) {
                $table->dropColumn('ongkir');
            });
        }
    }
};