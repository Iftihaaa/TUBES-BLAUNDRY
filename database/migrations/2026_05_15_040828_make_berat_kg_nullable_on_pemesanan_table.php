<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pemesanan', 'berat_kg')) {
            Schema::table('pemesanan', function (Blueprint $table) {
                $table->decimal('berat_kg', 8, 2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pemesanan', 'berat_kg')) {
            Schema::table('pemesanan', function (Blueprint $table) {
                $table->decimal('berat_kg', 8, 2)->nullable(false)->change();
            });
        }
    }
};