<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
//buat relasi ke tabel penggajian
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pencatatan_biaya', function (Blueprint $table) {
            // mengedit tabel pencatatan_biaya
            if (!Schema::hasColumn('pencatatan_biaya', 'id_penggajian')) {
                  // cek apakah kolom id_penggajian sudah ada
                $table->unsignedBigInteger('id_penggajian')->nullable()->after('id_coa'); //Menyimpan id penggajian.
                $table->foreign('id_penggajian')
                      ->references('id')
                      ->on('penggajian')
                      ->nullOnDelete();
                      // menambahkan kolom id_penggajian
                // nullOnDelete = jika data penggajian dihapus maka id_penggajian menjadi null

            }
            if (!Schema::hasColumn('pencatatan_biaya', 'bulan_penggajian')) { //// cek apakah kolom bulan_penggajian sudah ada
                $table->string('bulan_penggajian')->nullable()->after('id_penggajian'); //Menyimpan label bulan penggajian untuk memudahkan tampilan di UI.
            }
        });
    }

    public function down(): void
    {
        Schema::table('pencatatan_biaya', function (Blueprint $table) {
            $table->dropForeign(['id_penggajian']); // menghapus relasi foreign key
            $table->dropColumn(['id_penggajian', 'bulan_penggajian']);
        });
    }
};