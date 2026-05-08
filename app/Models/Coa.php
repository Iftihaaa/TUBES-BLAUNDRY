<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    protected $table = 'akun_coas';

    protected $fillable = [
        'header_akun',
        'kode_akun',
        'nama_akun',
    ];

    public function pembelians()
    {
        return $this->hasMany(Pembelian::class, 'coa_id');
    }
}