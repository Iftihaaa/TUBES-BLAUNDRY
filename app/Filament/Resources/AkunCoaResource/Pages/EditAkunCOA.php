<?php

namespace App\Filament\Resources\AkunCOAResource\Pages;

use App\Filament\Resources\AkunCOAResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAkunCOA extends EditRecord
{
    protected static string $resource = AkunCOAResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
