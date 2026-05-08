<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pemesanan extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'layanan_id',
        'tgl_pesan',
        'berat_kg',
        'total_harga',
        'status',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function layanan()
{
    return $this->belongsTo(Layanan::class, 'layanan_id', 'id_layanan');
}

    public function pembayarans()
    {
        return $this->hasMany(Pembayaran::class);
    }
}