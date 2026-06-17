<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParfumTrend extends Model
{
    protected $table = 'parfum_trends';

    protected $fillable = [
        'nama_tren',
        'analisis_ai',
        'parfum_populer',
        'aroma_terpopuler',
        'rekomendasi',
    ];

    protected $casts = [
        'parfum_populer' => 'array',
    ];
}