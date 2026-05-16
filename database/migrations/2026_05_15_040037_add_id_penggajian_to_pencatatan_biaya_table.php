<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pencatatan_biaya', function (Blueprint $table) {
            if (!Schema::hasColumn('pencatatan_biaya', 'id_penggajian')) {
                $table->unsignedBigInteger('id_penggajian')->nullable()->after('id_coa');
                $table->foreign('id_penggajian')
                      ->references('id')
                      ->on('penggajian')
                      ->nullOnDelete();
            }
            if (!Schema::hasColumn('pencatatan_biaya', 'bulan_penggajian')) {
                $table->string('bulan_penggajian')->nullable()->after('id_penggajian');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pencatatan_biaya', function (Blueprint $table) {
            $table->dropForeign(['id_penggajian']);
            $table->dropColumn(['id_penggajian', 'bulan_penggajian']);
        });
    }
};