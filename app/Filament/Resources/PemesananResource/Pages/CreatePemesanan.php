<?php

namespace App\Filament\Resources\PemesananResource\Pages;

use App\Filament\Resources\PemesananResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreatePemesanan extends CreateRecord
{
    protected static string $resource = PemesananResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Sembunyikan semua tombol bawaan Filament (Create, Create & create another).
     * Penyimpanan data ditangani sepenuhnya oleh tombol custom di dalam wizard:
     *   - "Proses Pesanan"   → simpan pemesanan + detail_pemesanan
     *   - "Simpan Pembayaran" → simpan pembayaran
     * Tombol "Batal" tetap ada agar user bisa keluar.
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction()
                ->label('Batal')
                ->color('gray'),
        ];
    }

    /**
     * Whitelist ketat — hanya kolom yang ada di tabel pemesanan.
     * Dipanggil jika Filament somehow masih trigger create (safety net).
     * id_layanan & berat_kg sudah dihapus dari tabel.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $isAmbil = ($data['pengantaran'] ?? 'ambil sendiri') === 'ambil sendiri';
        $ongkir  = $isAmbil ? 0 : (float) ($data['ongkir'] ?? 0);

        return [
            'kode_pemesanan' => $data['kode_pemesanan'],
            'tgl_pesan'      => $data['tgl_pesan'] ?? today()->toDateString(),
            'id_pelanggan'   => $data['id_pelanggan'],
            'status'         => $data['status'] ?? 'on process',
            'total_harga'    => (float) ($data['total_harga'] ?? 0),
            'ongkir'         => $ongkir,
            'pengantaran'    => $data['pengantaran'] ?? 'ambil sendiri',
        ];
    }
}