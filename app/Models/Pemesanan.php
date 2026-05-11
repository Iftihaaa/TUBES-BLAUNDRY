<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Pemesanan extends Model
{
    use HasFactory;

    protected $table = 'pemesanan';
    protected $primaryKey = 'id_pemesanan';

    protected $guarded = [];

    // Auto-generate kode pemesanan
    public static function getKodePemesanan()
    {
        $sql = "SELECT IFNULL(MAX(id_pemesanan), 'PES-0000000') as id_pemesanan FROM pemesanan";
        $kode = DB::select($sql);

        foreach ($kode as $k) {
            $kd = $k->id_pemesanan;
        }

        $noawal  = substr($kd, -7);
        $noakhir = $noawal + 1;
        $noakhir = 'PES-' . str_pad($noakhir, 7, "0", STR_PAD_LEFT);

        return $noakhir;
    }

    // Relasi ke tabel members (pelanggan)
    public function member()
    {
        return $this->belongsTo(Member::class, 'id_pelanggan', 'id_pelanggan');
    }

    // Relasi ke tabel pembayaran (1 pemesanan → 1 pembayaran)
    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'id_pemesanan', 'id_pemesanan');
    }

    // Relasi ke detail_pemesanan
    public function detailPemesanan()
    {
        return $this->hasMany(DetailPemesanan::class, 'id_pemesanan', 'id_pemesanan');
    }

    // Relasi M-M ke layanan lewat detail_pemesanan
    public function layanans()
    {
        return $this->belongsToMany(
            Layanan::class,
            'detail_pemesanan',
            'id_pemesanan',
            'id_layanan'
        )->withPivot('nama_layanan', 'harga_per_kg', 'berat_kg', 'subtotal')
         ->withTimestamps();
    }
}