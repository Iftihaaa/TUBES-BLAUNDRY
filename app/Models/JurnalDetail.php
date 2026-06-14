<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalDetail extends Model
{
    use HasFactory;

    protected $table = 'jurnal_detail'; // Nama tabel eksplisit

    protected $guarded = [];

    // relasi ke tabel jurnal (FK: jurnal_id)
    public function jurnal()
    {
        return $this->belongsTo(Jurnal::class, 'jurnal_id');
    }

    // relasi ke tabel coa (FK: coa_id)
    public function coa()
    {
        return $this->belongsTo(Coa::class, 'coa_id');
    }
}