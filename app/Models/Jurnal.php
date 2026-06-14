<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jurnal extends Model
{
    use HasFactory;

    protected $table = 'jurnal'; // Nama tabel eksplisit

    protected $guarded = []; //agar seluruh kolom dapat dimodifikasi

    // relasi ke jurnal detail 1-N
    public function jurnaldetail()
    {
        return $this->hasMany(JurnalDetail::class);
    }

}