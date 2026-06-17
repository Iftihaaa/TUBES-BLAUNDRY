<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// 1. PASTIKAN NAMESPACE HASFACTORY SEPERTI DI BAWAH INI:
use Illuminate\Database\Eloquent\Factories\HasFactory; 

class AnalisisCashflow extends Model
{
    // 2. Trait digunakan di dalam class
    use HasFactory; 

    protected $table = 'analisis_cashflow'; // sesuaikan dengan nama tabel migrationmu

    protected $fillable = [
        'periode',
        'analisis_ai',
        'kesimpulan',
        'saran_operasional',
    ];
}