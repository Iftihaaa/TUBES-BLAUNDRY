<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pegawai;
use App\Models\AkunCoa;
use App\Models\Penggajian;

class PencatatanBiaya extends Model
{
    use HasFactory;

    protected $table = 'pencatatan_biaya';
    protected $primaryKey = 'id_pencatatan_beban';
    protected $guarded = [];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai');
    }

    public function coa()
    {
        return $this->belongsTo(AkunCoa::class, 'id_coa');
    }

    // Relasi ke Penggajian
    public function penggajian()
    {
        return $this->belongsTo(Penggajian::class, 'id_penggajian', 'id');
    }
}