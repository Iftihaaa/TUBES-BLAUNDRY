<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PembelianResource\Pages;
use App\Models\AkunCOA;
use App\Models\Pegawai;
use App\Models\Pembelian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Pembelian';
    protected static ?string $navigationGroup = 'Transaksi';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Pembelian')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_faktur')
                            ->label('Nomor Faktur')
                            ->placeholder('Contoh: INV-001')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Select::make('pegawai_id')
                            ->label('Nama Pegawai')
                            ->relationship('pegawai', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('coa_id')
    ->label('Chart of Account')
    ->options(function () {
        return AkunCOA::query()
            ->orderBy('kode_akun')
            ->get()
            ->mapWithKeys(function ($coa) {
                return [
                    $coa->id => $coa->kode_akun . ' - ' . $coa->nama_akun,
                ];
            });
    })
    ->searchable()
    ->preload()
    ->required(),
                        Forms\Components\DatePicker::make('tanggal_beli')
                            ->label('Tanggal Beli')
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('jenis_pembelian')
                            ->label('Jenis Pembelian')
                            ->placeholder('Contoh: Paket laundry, produk tambahan, dll')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('harga_beli')
                            ->label('Harga Beli')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                $hargaBeli = (float) ($get('harga_beli') ?? 0);
                                $jumlah = (int) ($get('jumlah') ?? 1);

                                if ($jumlah < 1) {
                                    $jumlah = 1;
                                }

                                $set('total_harga', $hargaBeli * $jumlah);
                            }),

                        Forms\Components\TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                $hargaBeli = (float) ($get('harga_beli') ?? 0);
                                $jumlah = (int) ($get('jumlah') ?? 1);

                                if ($jumlah < 1) {
                                    $jumlah = 1;
                                }

                                $set('total_harga', $hargaBeli * $jumlah);
                            }),

                        Forms\Components\TextInput::make('total_harga')
                            ->label('Total Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'lunas' => 'Lunas',
                                'hutang' => 'Hutang',
                            ])
                            ->default('hutang')
                            ->required(),

                        Forms\Components\FileUpload::make('file_pembelian')
                            ->label('Upload File')
                            ->disk('public')
                            ->directory('file-pembelian')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_faktur')
                    ->label('Nomor Faktur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pegawai.nama')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('coa.kode_akun')
                    ->label('Kode Akun')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('coa.nama_akun')
                    ->label('Nama Akun')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_beli')
                    ->label('Tanggal Beli')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis_pembelian')
                    ->label('Jenis Pembelian')
                    ->searchable(),

                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'lunas' => 'success',
                        'hutang' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'lunas' => 'Lunas',
                        'hutang' => 'Hutang',
                        default => '-',
                    }),

                Tables\Columns\TextColumn::make('file_pembelian')
                    ->label('File')
                    ->formatStateUsing(fn ($state) => $state ? 'Lihat File' : '-')
                    ->url(fn ($record) => $record->file_pembelian
                        ? Storage::disk('public')->url($record->file_pembelian)
                        : null
                    )
                    ->openUrlInNewTab(),
            ])
            ->filters([
                //
            ])
           ->actions([
    Tables\Actions\ViewAction::make()
        ->label('View')
        ->icon('heroicon-o-eye')
        ->color('info'),

    Tables\Actions\EditAction::make()
        ->label('Edit')
        ->icon('heroicon-o-pencil-square'),

    Tables\Actions\DeleteAction::make()
        ->label('Delete')
        ->icon('heroicon-o-trash'),
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
            'index' => Pages\ListPembelians::route('/'),
            'create' => Pages\CreatePembelian::route('/create'),
            'edit' => Pages\EditPembelian::route('/{record}/edit'),
        ];
    }
}