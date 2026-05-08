<?php

namespace App\Filament\Resources\PemesananResource\Pages;

use App\Filament\Resources\PemesananResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

use App\Models\Pemesanan;
use App\Models\Pembayaran;
use App\Models\DetailPemesanan;
use Illuminate\Support\Facades\DB;

use Filament\Notifications\Notification;

class CreatePemesanan extends CreateRecord
{
    protected static string $resource = PemesananResource::class;

    // Set status default sebelum disimpan
    protected function beforeCreate(): void
    {
        $this->data['status']  = $this->data['status'] ?? 'on process';
        $this->data['ongkir']  = $this->data['ongkir'] ?? 0;
    }

    // Tombol aksi form
    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('proses')
                ->label('Simpan Pemesanan')
                ->color('success')
                ->action(fn () => $this->simpanPemesanan())
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pemesanan')
                ->modalDescription('Apakah Anda yakin ingin menyimpan pemesanan ini?')
                ->modalButton('Ya, Simpan'),
        ];
    }

    // Logika simpan pemesanan
    protected function simpanPemesanan()
    {
        $pemesanan = $this->record ?? Pemesanan::latest()->first();

        // Simpan detail pemesanan
        DetailPemesanan::create([
            'id_pemesanan' => $pemesanan->id_pemesanan,
            'id_layanan'   => $pemesanan->id_layanan,
            'subtotal'     => $pemesanan->total_harga, // auto-hitung di model DetailPemesanan
        ]);

        // Update status jadi on process
        $pemesanan->update(['status' => 'on process']);

        Notification::make()
            ->title('Pemesanan Berhasil Disimpan!')
            ->success()
            ->send();
    }

    // Redirect setelah create
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}