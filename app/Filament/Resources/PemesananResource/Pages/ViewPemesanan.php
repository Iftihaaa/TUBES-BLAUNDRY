<?php

namespace App\Filament\Resources\PemesananResource\Pages;

use App\Filament\Resources\PemesananResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewPemesanan extends ViewRecord
{
    protected static string $resource = PemesananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Pesanan'),

            // Cancel di header (kanan atas) — fallback jika footer tidak support
            Actions\Action::make('cancel')
                ->label('Close')
                ->color('gray')
                ->icon('heroicon-o-x-circle')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}