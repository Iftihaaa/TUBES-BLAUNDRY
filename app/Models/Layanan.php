<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Layanan extends Model
{
   use HasFactory;

    protected $table = 'layanan'; // nama tabel eksplisit

    protected $primaryKey = 'id_layanan'; // karena pakai custom id

    protected $guarded = [];
}
