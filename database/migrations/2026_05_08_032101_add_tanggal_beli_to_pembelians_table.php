<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            if (! Schema::hasColumn('pembelians', 'tanggal_beli')) {
                $table->date('tanggal_beli')->nullable()->after('nomor_faktur');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            if (Schema::hasColumn('pembelians', 'tanggal_beli')) {
                $table->dropColumn('tanggal_beli');
            }
        });
    }
};