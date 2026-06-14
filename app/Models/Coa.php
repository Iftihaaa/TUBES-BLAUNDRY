<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    /** @use HasFactory<\Database\Factories\CoaFactory> */
    use HasFactory;

    // tabel yang dipakai adalah akuncoa
    protected $table = 'akuncoa';

    // seluruh kolom dapat dimodifikasi
    protected $guarded = [];
}