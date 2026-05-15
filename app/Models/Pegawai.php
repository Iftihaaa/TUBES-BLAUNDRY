<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $table = 'pegawais';

    protected $primaryKey = 'id_pegawai';

    protected $fillable = [
        'nama',
        'jabatan',
        'no_telp',
        'alamat',
        'gaji_pokok',
    ];

    protected $casts = [
        'gaji_pokok' => 'decimal:2',
    ];

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'id_pegawai', 'id_pegawai');
    }
}