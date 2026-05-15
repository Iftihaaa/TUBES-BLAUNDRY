<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PencatatanBiayaResource\Pages;
use App\Models\PencatatanBiaya;
use App\Models\AkunCoa; 
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

// Import Components
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;

// Import Actions
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use App\Filament\Exports\PencatatanBiayaExporter;
use Filament\Tables\Actions\Action;

class PencatatanBiayaResource extends Resource
{
    protected static ?string $model = PencatatanBiaya::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Pencatatan Biaya';   
    protected static ?string $navigationGroup = 'Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // STEP 1: Tanggal
                    Wizard\Step::make('Tanggal Pencatatan')
                        ->schema([
                            DatePicker::make('tanggal_catat')
                                ->label('Tanggal Catat')
                                ->required(),
                        ]),

                    // STEP 2: Nama Pegawai
                    Wizard\Step::make('Identitas Pegawai')
                        ->schema([
                            Select::make('id_pegawai')
                                ->label('Nama Pegawai yang Mencatat')
                                ->relationship('pegawai', 'nama')
                                ->required()
                                ->searchable()
                                ->preload(),
                        ]),

                    // STEP 3: Gabungan Akun Beban & Detail Data
                    Wizard\Step::make('Lengkapi Detail Biaya')
                        ->schema([
                            Select::make('id_coa')
                                ->label('Pilih Akun Beban')
                                ->relationship(
                                    name: 'coa',
                                    titleAttribute: 'nama_akun',
                                    modifyQueryUsing: fn (Builder $query) => $query->where('header_akun', 5),
                                )
                                ->required()
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    $akun = AkunCoa::find($state);
                                    if ($akun) {
                                        $set('jenis_beban', $akun->nama_akun);
                                    }
                                })
                                ->afterStateHydrated(function ($state, Forms\Set $set) {
                                    $akun = AkunCoa::find($state);
                                    if ($akun) {
                                        $set('jenis_beban', $akun->nama_akun);
                                    }
                                }),
                            
                            // Input ini muncul HANYA JIKA memilih "Beban lain-lain"
                            TextInput::make('keterangan_beban_manual')
                                ->label('Nama Beban Manual')
                                ->placeholder('Contoh: Biaya Servis Mesin')
                                ->required(fn (Forms\Get $get) => optional(AkunCoa::find($get('id_coa')))->nama_akun === 'Beban lain-lain')
                                ->visible(fn (Forms\Get $get) => optional(AkunCoa::find($get('id_coa')))->nama_akun === 'Beban lain-lain')
                                ->reactive()
                                ->dehydrated(false)
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $set('jenis_beban', $state);
                                    }
                                })
                                ->columnSpanFull(),

                            Hidden::make('jenis_beban')
                                ->required()
                                ->reactive(),

                            TextInput::make('nominal')
                                ->label('Nominal')
                                ->numeric()
                                ->prefix('Rp')
                                ->required(),

                            Textarea::make('keterangan')
                                ->label('Keterangan Tambahan')
                                ->nullable(),

                            Placeholder::make('confirmation')
                                ->label('Konfirmasi')
                                ->content('Periksa kembali akun beban dan nominal sebelum menyimpan.'),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_catat')->label('Tanggal')->date()->sortable(),
                Tables\Columns\TextColumn::make('pegawai.nama')->label('Pegawai')->searchable(),
                Tables\Columns\TextColumn::make('jenis_beban')->label('Jenis Beban')->searchable(),
                Tables\Columns\TextColumn::make('nominal')->label('Nominal')->money('IDR', locale: 'id')->sortable(),
                Tables\Columns\TextColumn::make('keterangan')->label('Keterangan')->limit(30)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(PencatatanBiayaExporter::class)
                    ->color('success'),
                
                Action::make('print')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(fn () => null)
                    ->extraAttributes([
                        'onclick' => 'window.print()',
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
                ExportBulkAction::make()
                    ->exporter(PencatatanBiayaExporter::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPencatatanBiayas::route('/'),
            'create' => Pages\CreatePencatatanBiaya::route('/create'),
            'edit' => Pages\EditPencatatanBiaya::route('/{record}/edit'),
        ];
    }
}