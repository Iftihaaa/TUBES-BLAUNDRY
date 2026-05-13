<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPemesanan extends Model
{
    use HasFactory;

    protected $table      = 'detail_pemesanan';
    protected $primaryKey = 'id_detail_pemesanan'; // ← ini yang kurang
    protected $guarded    = [];

    // Otomatis hitung subtotal sebelum disimpan
    protected static function booted()
    {
        static::saving(function ($detail) {
            $detail->subtotal = $detail->harga_per_kg * $detail->berat_kg;
        });
    }

    // Relasi ke pemesanan
    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class, 'id_pemesanan', 'id_pemesanan');
    }

    // Relasi ke layanan
    public function layanan()
    {
        return $this->belongsTo(Layanan::class, 'id_layanan', 'id_layanan');
    }
}