<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pembayarans');
        Schema::dropIfExists('pemesanans');
    }

    public function down(): void
    {
        //
    }
};