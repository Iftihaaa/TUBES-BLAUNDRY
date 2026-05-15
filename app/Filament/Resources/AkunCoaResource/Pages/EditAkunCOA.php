<?php

namespace App\Filament\Resources\AkunCoaResource\Pages;

use App\Filament\Resources\AkunCoaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAkunCOA extends EditRecord
{
    protected static string $resource = AkunCoaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
