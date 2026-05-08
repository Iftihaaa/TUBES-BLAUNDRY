<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Member extends Model
{
    use HasFactory;

    protected $table = 'members';

    protected $primaryKey = 'id_pelanggan'; // karena pakai custom id

    protected $guarded = [];

    // Auto-generate kode member
    public static function getIdPelanggan()
    {
        $sql = "SELECT IFNULL(MAX(id_pelanggan), 'MBR-00000') as id_pelanggan 
                FROM members";
        $kode = DB::select($sql);

        foreach ($kode as $k) {
            $kd = $k->id_pelanggan;
        }

        $noawal  = substr($kd, -5);
        $noakhir = $noawal + 1;
        $noakhir = 'MBR-' . str_pad($noakhir, 5, "0", STR_PAD_LEFT);

        return $noakhir;
    }

    // Relasi ke tabel user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke tabel pemesanan
    public function pemesanan()
    {
        return $this->hasMany(Pemesanan::class, 'id_pelanggan', 'id');
    }

    // Relasi ke tabel pembayaran (lewat pemesanan)
    public function pembayarans()
    {
        return $this->hasManyThrough(
            Pembayaran::class,
            Pemesanan::class,
            'id_pelanggan', // FK di pemesanan
            'id_pemesanan', // FK di pembayaran
            'id_pelanggan',           // PK di members
            'id_pemesanan'  // PK di pemesanan
        );
    }
}