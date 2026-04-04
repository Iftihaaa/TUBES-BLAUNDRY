<?php

namespace App\Filament\Resources;

// Tambahan
use Filament\Forms\Components\TextInput; //kita menggunakan textinput
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;

use App\Filament\Resources\AkunCoaResource\Pages;
use App\Filament\Resources\AkunCoaResource\RelationManagers;
use App\Models\AkunCoa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AkunCoaResource extends Resource
{
    protected static ?string $model = AkunCoa::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'CoA';
    protected static ?string $pluralLabel = 'CoA';
    protected static ?string $label = 'CoA';

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                 //isikan dengan input type form
                Grid::make(1) // Membuat hanya 1 kolom
                ->schema([
                    TextInput::make('header_akun')
                        ->required()
                        ->placeholder('Masukkan header akun')
                    ,
                    TextInput::make('kode_akun')
                        ->required()
                        ->placeholder('Masukkan kode akun')
                    ,
                    TextInput::make('nama_akun')
                        ->autocapitalize('words')
                        ->label('Nama akun')
                        ->required()
                        ->placeholder('Masukkan nama akun')
                    ,
                ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                 //isikan kolom mana saja yang akan ditampilkan di sini
                TextColumn::make('header_akun'),
                TextColumn::make('kode_akun'),
                TextColumn::make('nama_akun'), 
            ])
            ->filters([
                  //untuk membuat filter 
                Tables\Filters\SelectFilter::make('header_akun')
                    ->options([
                        1 => 'Aset/Aktiva',
                        2 => 'Utang',
                        3 => 'Modal',
                        4 => 'Pendapatan',
                        5 => 'Beban',
                    ]),
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
            'index' => Pages\ListAkunCoas::route('/'),
            'create' => Pages\CreateAkunCoa::route('/create'),
            'edit' => Pages\EditAkunCoa::route('/{record}/edit'),
        ];
    }
}
// update git