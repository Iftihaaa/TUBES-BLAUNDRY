<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pegawais', 'gaji_pokok')) {
            Schema::table('pegawais', function (Blueprint $table) {
                $table->decimal('gaji_pokok', 10, 2)
                    ->default(0)
                    ->after('alamat');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pegawais', 'gaji_pokok')) {
            Schema::table('pegawais', function (Blueprint $table) {
                $table->dropColumn('gaji_pokok');
            });
        }
    }
};