<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'pembelians';

    protected $fillable = [
        'nomor_faktur',
        'pegawai_id',
        'coa_id',
        'tanggal_beli',
        'jenis_pembelian',
        'harga_beli',
        'jumlah',
        'total_harga',
        'status',
        'file_pembelian',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_beli' => 'date',
        'harga_beli' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'jumlah' => 'integer',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }

    public function coa()
{
    return $this->belongsTo(AkunCOA::class, 'coa_id');
}

    protected static function booted(): void
    {
        static::saving(function (Pembelian $pembelian) {
            $hargaBeli = (float) ($pembelian->harga_beli ?? 0);
            $jumlah = (int) ($pembelian->jumlah ?? 1);

            if ($jumlah < 1) {
                $jumlah = 1;
            }

            $pembelian->jumlah = $jumlah;
            $pembelian->total_harga = $hargaBeli * $jumlah;
        });
    }
}