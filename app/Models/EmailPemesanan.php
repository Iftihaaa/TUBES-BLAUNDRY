<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailPemesanan extends Model
{
    use HasFactory;

    protected $table = 'email_pemesanan'; // Nama tabel eksplisit

    protected $guarded = []; //semua kolom boleh di isi

    // relasi ke tabel pemesanan
    public function pemesanan()
    {
        return $this->belongsTo(Pemesanan::class, 'id_pemesanan', 'id_pemesanan');
    }
}