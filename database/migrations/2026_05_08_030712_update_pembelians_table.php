<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            if (! Schema::hasColumn('pembelians', 'nomor_faktur')) {
                $table->string('nomor_faktur')->nullable()->after('id');
            }

            if (! Schema::hasColumn('pembelians', 'harga_beli')) {
                $table->decimal('harga_beli', 15, 2)->default(0)->after('jenis_pembelian');
            }

            if (! Schema::hasColumn('pembelians', 'jumlah')) {
                $table->integer('jumlah')->default(1)->after('harga_beli');
            }

            if (! Schema::hasColumn('pembelians', 'total_harga')) {
                $table->decimal('total_harga', 15, 2)->default(0)->after('jumlah');
            }

            if (! Schema::hasColumn('pembelians', 'file_pembelian')) {
                $table->string('file_pembelian')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            if (Schema::hasColumn('pembelians', 'nomor_faktur')) {
                $table->dropColumn('nomor_faktur');
            }

            if (Schema::hasColumn('pembelians', 'jumlah')) {
                $table->dropColumn('jumlah');
            }

            if (Schema::hasColumn('pembelians', 'file_pembelian')) {
                $table->dropColumn('file_pembelian');
            }
        });
    }
};