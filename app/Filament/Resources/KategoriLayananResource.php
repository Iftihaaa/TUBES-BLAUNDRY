<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KategoriLayananResource\Pages;
use App\Filament\Resources\KategoriLayananResource\RelationManagers;
use App\Models\KategoriLayanan;
use Filament\Forms\Components\Radio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// tambahan untuk komponen input form
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

// tambahan untuk komponen kolom
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\Grid;

class KategoriLayananResource extends Resource
{
    protected static ?string $model = KategoriLayanan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Kategori Layanan';
    protected static ?string $pluralLabel = 'Kategori Layanan';
    protected static ?string $label = 'Kategori Layanan';

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Radio::make('nama_kategori')
                    ->label('Nama Kategori')
                    ->options([
                        'Satuan' => 'Satuan',
                        'Kiloan' => 'Kiloan',
                    ])
                    ->required(),
                Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->maxLength(500)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_kategori')
                    ->label('Nama Kategori'),
                    
                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKategoriLayanans::route('/'),
            'create' => Pages\CreateKategoriLayanan::route('/create'),
            'edit' => Pages\EditKategoriLayanan::route('/{record}/edit'),
        ];
    }
}
