<?php

namespace App\Filament\Resources\BukuBesarResource\Pages;

use App\Filament\Resources\BukuBesarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use App\Filament\Resources\BukuBesarResource\Widgets\BukuBesar;

class ListBukuBesars extends ListRecords
{
    protected static string $resource = BukuBesarResource::class;

    public function getTableRecords(): \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Database\Eloquent\Collection
    {
        return new \Illuminate\Database\Eloquent\Collection();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BukuBesar::class,
        ];
    }
}