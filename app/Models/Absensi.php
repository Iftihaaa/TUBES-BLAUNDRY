<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $table = 'absensis';

    protected $fillable = [
        'pegawai_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'status',
        'keterangan',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id', 'id');
    }
}