<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisaLabaRugi extends Model
{
    use HasFactory;

    protected $table = 'analisa_laba_rugi';

    protected $fillable = [
        'bulan',
        'tahun',
        'total_pendapatan',
        'total_modal',
        'total_beban',
        'laba_bersih',
        'status_keuangan',
        'ringkasan',
        'analisis_pendapatan',
        'analisis_beban',
        'analisis_margin',
        'rekomendasi',
        'kesimpulan',
        'raw_response',
    ];

    protected $casts = [
        'rekomendasi' => 'array',
        'total_pendapatan' => 'decimal:2',
        'total_modal' => 'decimal:2',
        'total_beban' => 'decimal:2',
        'laba_bersih' => 'decimal:2',
    ];
}