<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengirimanEmail extends Model
{
    use HasFactory;

    protected $table = 'pengiriman_email'; // Nama tabel eksplisit

    protected $guarded = []; //semua kolom boleh di isi

    // relasi ke tabel penjualan
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }
}