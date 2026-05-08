<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Layanan extends Model
{
    use HasFactory;

    protected $table = 'layanan';

    protected $primaryKey = 'id_layanan';

    protected $fillable = [
        'nama_layanan',
        'harga_per_kg',
        'deskripsi',
        'gambar',
        'is_admin',
    ];

    public function pemesanans()
    {
        return $this->hasMany(Pemesanan::class, 'layanan_id', 'id_layanan');
    }
}