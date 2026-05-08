<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $table = 'members';

    protected $fillable = [
        'nama_pelanggan',
        'no_telp',
        'alamat',
    ];

    public function pemesanans()
    {
        return $this->hasMany(Pemesanan::class, 'member_id');
    }

    public function pembelians()
    {
        return $this->hasMany(Pembelian::class, 'member_id');
    }
}