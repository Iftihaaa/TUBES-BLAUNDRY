<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    // tambahan penyebutan tabel secara eksplisit
    protected $table = 'pembayaran'; // Nama tabel eksplisit

    protected $primaryKey = 'id_pembayaran'; // ← tambahkan ini

    // proteksi kolom tabel (tidak ada yg diproteksi)
    protected $guarded = [];

    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class, 'id_pemesanan', 'id_pemesanan');
    }
}
