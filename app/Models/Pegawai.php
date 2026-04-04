<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $fillable = [
    'nama',
    'jabatan',
    'no_telp',
    'alamat',
];
}