<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabaRugiResource\Pages;
use App\Models\JurnalDetail;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;

class LabaRugiResource extends Resource
{
    protected static ?string $model = JurnalDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laba Rugi';

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
            'index' => Pages\ListLabaRugis::route('/'),
        ];
    }
}