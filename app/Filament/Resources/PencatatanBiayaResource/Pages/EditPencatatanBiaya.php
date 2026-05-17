<?php

namespace App\Filament\Resources\PencatatanBiayaResource\Pages;

use App\Filament\Resources\PencatatanBiayaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPencatatanBiaya extends EditRecord
{
    protected static string $resource = PencatatanBiayaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
