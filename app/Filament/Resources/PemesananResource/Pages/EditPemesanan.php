<?php

namespace App\Filament\Resources\PemesananResource\Pages;

use App\Filament\Resources\PemesananResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPemesanan extends EditRecord
{
    protected static string $resource = PemesananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Load detail_pemesanan ke state items_layanan
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        $data['saved_id_pemesanan'] = $record->id_pemesanan;

        $data['items_layanan'] = $record->detailPemesanan->map(fn ($detail) => [
            'id_layanan'   => $detail->id_layanan,
            'harga_satuan' => $detail->harga_per_kg,
            'jumlah'       => $detail->berat_kg,
            'subtotal'     => $detail->subtotal,
            'is_satuan'    => optional($detail->layanan->kategoriLayanan)->nama_kategori === 'Satuan' ? '1' : '0',
        ])->toArray();

        return $data;
    }
}