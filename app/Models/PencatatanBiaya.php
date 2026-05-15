<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// Pastikan nama model sesuai dengan file yang ada di folder Models
use App\Models\Pegawai; 
use App\Models\AkunCoa; // Ganti Coa jadi AkunCoa

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
        // Pastikan di sini pakai AkunCoa::class
        return $this->belongsTo(AkunCoa::class, 'id_coa'); 
    }
}