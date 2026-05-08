<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $table = 'pegawais';

    protected $fillable = [
        'nama',
        'jabatan',
        'no_telp',
        'alamat',
    ];

    public function pembelians()
    {
        return $this->hasMany(Pembelian::class, 'pegawai_id');
    }
}