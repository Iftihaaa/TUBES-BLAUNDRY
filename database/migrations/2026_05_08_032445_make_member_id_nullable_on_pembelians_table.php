<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pembelians', 'member_id')) {
            DB::statement('ALTER TABLE pembelians MODIFY member_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pembelians', 'member_id')) {
            DB::statement('ALTER TABLE pembelians MODIFY member_id BIGINT UNSIGNED NOT NULL');
        }
    }
};