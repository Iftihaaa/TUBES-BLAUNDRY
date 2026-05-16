<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PencatatanBiaya;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\LaporanBeban;

class PengirimanEmailController extends Controller
{
    public function proses_kirim_email_laporan_beban()
    {
        $biayas = PencatatanBiaya::with(['pegawai', 'coa'])->get();
        $message = '';

        if ($biayas->isEmpty()) {
            $message = 'Tidak ada data biaya untuk dikirim.';
            return view('email_status', [
                'message' => $message,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        }

        $recipient = User::first();
        if (!$recipient || !filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Tidak ada penerima email valid.';
            return view('email_status', [
                'message' => $message,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        }

        $dataAtributPelanggan = [
            'owner_name' => $recipient->name,
            'periode' => now()->format('F Y'),
            'total' => $biayas->sum('nominal'),
            'biayas' => $biayas,
        ];

        try {
            Mail::to($recipient->email)->send(new LaporanBeban($dataAtributPelanggan));
            $message = 'Email laporan biaya berhasil dikirim ke ' . $recipient->email;
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim email laporan beban: ' . $e->getMessage());
            $message = 'Gagal mengirim email: ' . $e->getMessage();
        }

        return view('email_status', [
            'message' => $message,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}