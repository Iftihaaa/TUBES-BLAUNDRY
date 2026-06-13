// database/migrations/XXXX_add_id_penggajian_to_pencatatan_biaya_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
//menambah kolom baru bulan_penggajian
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pencatatan_biaya', function (Blueprint $table) {
            // Tambah kolom id_penggajian (nullable karena tidak semua beban = gaji)
            $table->unsignedBigInteger('id_penggajian')->nullable()->after('id_coa');
            
            // Foreign key ke tabel penggajian
            $table->foreign('id_penggajian')
                  ->references('id')
                  ->on('penggajian')
                  ->nullOnDelete(); //Kalau data relasi dihapus, maka foreign key berubah jadi:

            // Tambah kolom bulan_penggajian untuk menampilkan label bulan
            $table->string('bulan_penggajian')->nullable()->after('id_penggajian');
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