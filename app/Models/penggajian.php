<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penggajian extends Model
{
    protected $table = 'penggajian'; 
    //Kasih tahu Laravel bahwa model ini terhubung ke tabel bernama 'penggajian' di database. 
    // Kalau tidak ada baris ini, Laravel akan cari tabel bernama 'penggajians' (otomatis ditambahin 's').

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
    //Daftar kolom yang BOLEH diisi dari form. 
    // Ini untuk keamanan — kolom yang tidak ada di sini tidak bisa diisi dari luar. 
    // Kalau kolom tidak masuk fillable, data tidak tersimpan meski form sudah diisi.

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai', 'id_pegawai');
    }
    //→ Relasi ke tabel pegawai. 
    // Artinya: 1 penggajian DIMILIKI OLEH 1 pegawai. 
    // Dengan relasi ini, kita bisa akses nama pegawai lewat $penggajian->pegawai->nama tanpa query manual.


    // Relasi balik ke PencatatanBiaya (opsional)
    public function pencatatanBiaya()
    {
        return $this->hasMany(PencatatanBiaya::class, 'id_penggajian', 'id');
    }
}