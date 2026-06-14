<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_detail', function (Blueprint $table) {
            $table->dropForeign('jurnal_detail_coa_id_foreign');

            $table->foreign('coa_id')
                ->references('id')
                ->on('akuncoa')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_detail', function (Blueprint $table) {
            $table->dropForeign('jurnal_detail_coa_id_foreign');

            $table->foreign('coa_id')
                ->references('id')
                ->on('coa')
                ->onDelete('cascade');
        });
    }
};