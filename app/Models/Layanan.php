<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\KategoriLayanan; // Tambah import untuk relationship

class Layanan extends Model
{
   use HasFactory;

    protected $table = 'layanan'; // nama tabel eksplisit

    protected $primaryKey = 'id_layanan'; // karena pakai custom id

    protected $guarded = [];

    public function kategoriLayanan()
    {
        return $this->belongsTo(KategoriLayanan::class, 'id_kategori_layanan', 'id_kategori_layanan');
    }

    // Relasi ke detail_pemesanan
    public function detailPemesanan()
    {
        return $this->hasMany(DetailPemesanan::class, 'id_layanan', 'id_layanan');
    }
}