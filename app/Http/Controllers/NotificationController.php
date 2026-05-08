<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FonnteService;
use App\Models\Member; // sesuaikan namespace model kamu

class NotificationController extends Controller
{
    protected $fonnteService;

    public function __construct(FonnteService $fonnteService)
    {
        $this->fonnteService = $fonnteService;
    }

    // 1. Saat pelanggan berhasil daftar member
    public function notifikasiDaftarMember($member)
    {
        $pesan = "Halo, {$member->nama_pelanggan}! 🎉\n"
               . "Selamat, kamu resmi jadi *Member Laundry* kami!\n\n"
               . "🪪 ID Member : #{$member->id}\n"
               . "📍 Alamat    : {$member->alamat}\n\n"
               . "Nikmati berbagai layanan laundry terbaik kami 🙏";

        return $this->fonnteService->sendMessage($member->no_telp, $pesan);
    }

    // 2. Saat order/transaksi baru masuk
    // (sesuaikan $order dengan model transaksi kamu nanti)
    public function notifikasiOrderBaru($order, $member)
    {
        $kategori = $order->layanan->kategoriLayanan->nama_kategori ?? '-';
        $satuan   = $kategori === 'Kiloan' ? "kg" : "pcs";

        $pesan = "Halo, {$member->nama_pelanggan}! 👋\n"
               . "Order laundry kamu *berhasil dibuat*.\n\n"
               . "🧺 Detail Order:\n"
               . "- No. Order  : #{$order->id}\n"
               . "- Layanan    : {$order->layanan->nama_layanan}\n"
               . "- Kategori   : {$kategori}\n"
               . "- Jumlah     : {$order->jumlah} {$satuan}\n"
               . "- Total      : Rp " . number_format($order->total_harga, 0, ',', '.') . "\n\n"
               . "Terima kasih sudah mempercayakan laundry ke kami! 🙏";

        return $this->fonnteService->sendMessage($member->no_telp, $pesan);
    }

    // 3. Saat status order berubah
    public function notifikasiStatusBerubah($order, $member)
    {
        $statusPesan = [
            'dicuci'       => '🫧 Laundry kamu sedang *dalam proses pencucian*.',
            'disetrika'    => '👔 Laundry kamu sedang *dalam proses penyetrikaan*.',
            'siap_diambil' => '✅ Laundry kamu *sudah selesai dan siap diambil*!',
            'selesai'      => '🎉 Terima kasih! Order kamu telah *selesai*.',
        ];

        $keterangan = $statusPesan[$order->status] ?? "Status diupdate: {$order->status}";

        $pesan = "Halo, {$member->nama_pelanggan}! 👋\n"
               . "Update order *#{$order->id}* - {$order->layanan->nama_layanan}:\n\n"
               . $keterangan . "\n\n"
               . "Ada pertanyaan? Hubungi kami ya 😊";

        return $this->fonnteService->sendMessage($member->no_telp, $pesan);
    }

    // 4. Saat pembayaran dikonfirmasi
    public function notifikasiPembayaran($order, $member)
    {
        $pesan = "Halo, {$member->nama_pelanggan}! 👋\n"
               . "Pembayaran order *#{$order->id}* sudah *kami terima* ✅\n\n"
               . "💰 Total Bayar : Rp " . number_format($order->total_harga, 0, ',', '.') . "\n"
               . "📅 Tanggal     : " . now()->format('d/m/Y H:i') . "\n\n"
               . "Terima kasih! 🙏";

        return $this->fonnteService->sendMessage($member->no_telp, $pesan);
    }
}