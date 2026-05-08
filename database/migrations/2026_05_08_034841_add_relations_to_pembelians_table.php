<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            if (! Schema::hasColumn('pembelians', 'pegawai_id')) {
                $table->unsignedBigInteger('pegawai_id')->nullable()->after('nomor_faktur');
            }

            if (! Schema::hasColumn('pembelians', 'coa_id')) {
                $table->unsignedBigInteger('coa_id')->nullable()->after('pegawai_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            if (Schema::hasColumn('pembelians', 'coa_id')) {
                $table->dropColumn('coa_id');
            }

            if (Schema::hasColumn('pembelians', 'pegawai_id')) {
                $table->dropColumn('pegawai_id');
            }
        });
    }
};