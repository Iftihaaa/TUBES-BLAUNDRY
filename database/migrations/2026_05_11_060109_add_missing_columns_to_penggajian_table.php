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
            if (!Schema::hasColumn('penggajian', 'id_penggajian')) {
                $table->string('id_penggajian')->unique();
            }

            if (!Schema::hasColumn('penggajian', 'tanggal_bayar')) {
                $table->date('tanggal_bayar')->nullable();
            }

            if (!Schema::hasColumn('penggajian', 'jumlah_tidak_hadir')) {
                $table->integer('jumlah_tidak_hadir')->default(0);
            }

            if (!Schema::hasColumn('penggajian', 'bonus')) {
                $table->string('bonus')->default('tidak');
            }

            if (!Schema::hasColumn('penggajian', 'nominal_bonus')) {
                $table->decimal('nominal_bonus', 15, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            $table->dropColumn([
                'id_penggajian',
                'tanggal_bayar',
                'jumlah_tidak_hadir',
                'bonus',
                'nominal_bonus',
            ]);
        });
    }
};
