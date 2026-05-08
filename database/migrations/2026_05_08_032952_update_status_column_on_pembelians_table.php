<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pembelians', 'status')) {
            DB::statement("ALTER TABLE pembelians MODIFY status VARCHAR(20) NOT NULL DEFAULT 'hutang'");

            DB::table('pembelians')
                ->whereNotIn('status', ['lunas', 'hutang'])
                ->update([
                    'status' => 'hutang',
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pembelians', 'status')) {
            DB::statement("ALTER TABLE pembelians MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        }
    }
};