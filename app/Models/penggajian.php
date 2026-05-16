<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penggajian extends Model
{
    protected $table = 'penggajian';

    protected $fillable = [
        'id_penggajian',
        'id_pegawai',
        'tanggal_bayar',
        'jumlah_hadir',
        'jumlah_tidak_hadir',
        'gaji_pokok',
        'bonus',
        'nominal_bonus',
        'total_gaji',
        'status_pembayaran',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }

    // Relasi balik ke PencatatanBiaya (opsional)
    public function pencatatanBiaya()
    {
        return $this->hasMany(PencatatanBiaya::class, 'id_penggajian', 'id');
    }
}