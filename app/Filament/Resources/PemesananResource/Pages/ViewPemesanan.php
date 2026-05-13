<?php

namespace App\Filament\Resources\PemesananResource\Pages;

use App\Filament\Resources\PemesananResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewPemesanan extends ViewRecord
{
    protected static string $resource = PemesananResource::class;

    // Semua field sudah readonly karena ini ViewRecord (Filament handle otomatis)
    // Tidak ada action edit di sini — hanya tombol back

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Pesanan'),
        ];
    }
}