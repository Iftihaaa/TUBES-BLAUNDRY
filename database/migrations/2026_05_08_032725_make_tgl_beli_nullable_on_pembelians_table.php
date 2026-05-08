<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pembelians', 'tgl_beli')) {
            DB::statement('ALTER TABLE pembelians MODIFY tgl_beli DATE NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pembelians', 'tgl_beli')) {
            DB::statement('ALTER TABLE pembelians MODIFY tgl_beli DATE NOT NULL');
        }
    }
};