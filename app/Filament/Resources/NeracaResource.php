<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NeracaResource\Pages;
use App\Models\JurnalDetail;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;

class NeracaResource extends Resource
{
    protected static ?string $model = JurnalDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Neraca';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNeracas::route('/'),
        ];
    }
}