<?php

namespace App\Http\Controllers;

use App\Models\EmailPemesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;
use App\Mail\SelesaiMail;
use Barryvdh\DomPDF\Facade\Pdf;

class EmailPemesananController extends Controller
{
    // =========================================================
    // PROSES SEMUA: Invoice + Selesai sekaligus
    // =========================================================
    public static function proses_semua()
    {
        self::proses_kirim_email_invoice();
        sleep(2); // ← jeda 2 detik sebelum kirim email berikutnya
        self::proses_kirim_email_selesai();
        return view('autorefresh_email');
    }

    // =========================================================
    // EMAIL 1: Invoice saat pembayaran berhasil
    // =========================================================
    public static function proses_kirim_email_invoice()
    {
        $data = DB::table('pemesanan')
            ->join('members', 'pemesanan.id_pelanggan', '=', 'members.id_pelanggan')
            ->join('pembayaran', 'pemesanan.id_pemesanan', '=', 'pembayaran.id_pemesanan')
            ->whereIn('pembayaran.status_message', [
                'Pembayaran tunai berhasil.',
                'midtrans payment notification',
            ])
            ->whereNotIn('pemesanan.id_pemesanan', function ($query) {
                $query->select('id_pemesanan')
                    ->from('email_pemesanan')
                    ->where('jenis_email', 'invoice');
            })
            ->select(
                'pemesanan.id_pemesanan',
                'pemesanan.kode_pemesanan',
                'pemesanan.total_harga',
                'pemesanan.pengantaran',
                'pemesanan.ongkir',
                'pemesanan.tgl_pesan',
                'members.nama_pelanggan',
                'members.email',
                'pembayaran.jenis_pembayaran',
                'pembayaran.tgl_bayar',
                'pembayaran.status_message',
            )
            ->first();

        if ($data) {
            $detail = DB::table('detail_pemesanan')
                ->join('layanan', 'detail_pemesanan.id_layanan', '=', 'layanan.id_layanan')
                ->where('detail_pemesanan.id_pemesanan', $data->id_pemesanan)
                ->select(
                    'detail_pemesanan.nama_layanan',
                    'detail_pemesanan.harga_per_kg',
                    'detail_pemesanan.berat_kg',
                    'detail_pemesanan.subtotal',
                )
                ->get();

            $pdf = Pdf::loadView('pdf.invoice_pemesanan', [
                'kode_pemesanan'   => $data->kode_pemesanan,
                'nama_pelanggan'   => $data->nama_pelanggan,
                'items'            => $detail,
                'total_harga'      => $data->total_harga,
                'ongkir'           => $data->ongkir,
                'pengantaran'      => $data->pengantaran,
                'tgl_pesan'        => $data->tgl_pesan,
                'jenis_pembayaran' => $data->jenis_pembayaran,
                'tgl_bayar'        => $data->tgl_bayar,
                'status_message'   => $data->status_message,
                'tanggal'          => now()->format('d-M-Y'),
            ]);

            $dataAtribut = [
                'customer_name'  => $data->nama_pelanggan,
                'kode_pemesanan' => $data->kode_pemesanan,
                'status_message' => $data->status_message,
            ];

            try {
                Mail::to($data->email)->send(new InvoiceMail($dataAtribut, $pdf->output()));
                
                EmailPemesanan::create([
                    'id_pemesanan'         => $data->id_pemesanan,
                    'jenis_email'          => 'invoice',
                    'status'               => 'sudah terkirim',
                    'tgl_pengiriman_pesan' => now(),
                ]);
            } catch (\Exception $e) {
                // Kalau gagal, tidak dicatat → otomatis retry di refresh berikutnya
            }
        }
    }

    // =========================================================
    // EMAIL 2: Notifikasi saat pesanan selesai (status = done)
    // =========================================================
    public static function proses_kirim_email_selesai()
    {
        $data = DB::table('pemesanan')
            ->join('members', 'pemesanan.id_pelanggan', '=', 'members.id_pelanggan')
            ->where('pemesanan.status', 'done')
            ->whereNotIn('pemesanan.id_pemesanan', function ($query) {
                $query->select('id_pemesanan')
                    ->from('email_pemesanan')
                    ->where('jenis_email', 'selesai');
            })
            ->select(
                'pemesanan.id_pemesanan',
                'pemesanan.kode_pemesanan',
                'pemesanan.pengantaran',
                'pemesanan.tgl_pesan',
                'members.nama_pelanggan',
                'members.email',
            )
            ->first();

        if ($data) {
            $dataAtribut = [
                'customer_name'  => $data->nama_pelanggan,
                'kode_pemesanan' => $data->kode_pemesanan,
                'pengantaran'    => $data->pengantaran,
            ];

            try {
                Mail::to($data->email)->send(new SelesaiMail($dataAtribut));

                EmailPemesanan::create([
                    'id_pemesanan'         => $data->id_pemesanan,
                    'jenis_email'          => 'selesai',
                    'status'               => 'sudah terkirim',
                    'tgl_pengiriman_pesan' => now(),
                ]);
            } catch (\Exception $e) {
                // Kalau gagal, tidak dicatat → otomatis retry di refresh berikutnya
            }
        }
    }
}