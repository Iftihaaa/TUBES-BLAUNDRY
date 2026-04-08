<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriLayanan extends Model
{
    use HasFactory;
    protected $table = 'kategori_layanan'; // Nama tabel eksplisit

    protected $guarded = [];

    public function layanans()
    {
        return $this->hasMany(Layanan::class, 'id_kategori_layanan', 'id');
    }
}
