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
        Schema::table('penggajian', function (Blueprint $table) {
            // $table->renameColumn('pegawais_id', 'id_pegawai');
            // $table->renameColumn('dapat_bonus', 'bonus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            // $table->renameColumn('pegawai_id', 'id_pegawai');
            // $table->renameColumn('bonus', 'dapat_bonus');
        });
    }
};