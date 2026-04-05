<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Models\Member;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

use Filament\Tables\Columns\TextColumn;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Pelanggan';
    protected static ?string $modelLabel = 'Pelanggan';
    protected static ?string $pluralModelLabel = 'Pelanggan';

    // ================= FORM =================
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_pelanggan')
                    ->label('Nama Pelanggan')
                    ->required(),

                TextInput::make('no_telp')
                    ->label('No Telp / WhatsApp')
                    ->tel()
                    ->required(),

                Textarea::make('alamat')
                    ->label('Alamat')
                    ->required()
                    ->rows(4),
            ]);
    }

    // ================= TABLE =================
    public static function table(Table $table): Table
    {
        return $table
           ->columns([
    TextColumn::make('id')
        ->label('ID Pelanggan')
        ->sortable(),

    TextColumn::make('nama_pelanggan')
        ->label('Nama Pelanggan'),

    TextColumn::make('no_telp')
        ->label('No Telp'),

    TextColumn::make('alamat')
        ->label('Alamat'),
])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }
}