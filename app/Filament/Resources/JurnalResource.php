<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JurnalResource\Pages;
use App\Models\Jurnal;
use App\Models\Coa;
use App\Models\JurnalDetail;
use App\Models\Pemesanan;
use App\Models\Pembelian;
use App\Models\PencatatanBiaya;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;

class JurnalResource extends Resource
{
    protected static ?string $model = Jurnal::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Jurnal Umum';
    protected static ?string $pluralModelLabel = 'Jurnal Umum';
    protected static ?string $navigationGroup = 'Laporan';

    private static function getDetailOtomatis(string $jenis, string $referensi): array
    {
        return match ($jenis) {
            'pemesanan' => (function () use ($referensi) {
                $data = Pemesanan::where('kode_pemesanan', $referensi)->first();
                if (!$data) return [];
                $nominal = (float) $data->total_harga;
                return [
                    ['coa_id' => 1, 'debit' => $nominal, 'credit' => 0,       'deskripsi' => 'Kas masuk dari ' . $referensi],
                    ['coa_id' => 5, 'debit' => 0,       'credit' => $nominal, 'deskripsi' => 'Pendapatan jasa laundry ' . $referensi],
                ];
            })(),
            'pembelian' => (function () use ($referensi) {
                $data = Pembelian::where('nomor_faktur', $referensi)->first();
                if (!$data) return [];
                $nominal   = (float) $data->total_harga;
                $kreditId  = $data->status === 'lunas' ? 1 : 2;
                $kreditKet = $data->status === 'lunas' ? 'Kas keluar pembelian ' : 'Utang dagang atas pembelian ';
                return [
                    ['coa_id' => 6, 'debit' => $nominal, 'credit' => 0,       'deskripsi' => 'Pembelian ' . $data->jenis_pembelian . ' - ' . $referensi],
                    ['coa_id' => $kreditId, 'debit' => 0, 'credit' => $nominal, 'deskripsi' => $kreditKet . $referensi],
                ];
            })(),
            'biaya' => (function () use ($referensi) {
                $data = PencatatanBiaya::where('id_pencatatan_beban', $referensi)->first();
                if (!$data) return [];
                $nominal = (float) $data->nominal;
                return [
                    ['coa_id' => $data->id_coa, 'debit' => $nominal, 'credit' => 0,       'deskripsi' => $data->jenis_beban ?? 'Beban'],
                    ['coa_id' => 1,              'debit' => 0,       'credit' => $nominal, 'deskripsi' => 'Kas keluar untuk ' . ($data->jenis_beban ?? 'biaya')],
                ];
            })(),
            default => [],
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Deskripsi Jurnal')
                    ->schema([
                        Select::make('jenis_transaksi')
                            ->label('Jenis Transaksi')
                            ->options([
                                'pemesanan' => 'Pemesanan',
                                'pembelian' => 'Pembelian',
                                'biaya'     => 'Pencatatan Biaya',
                            ])
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('no_referensi', null);
                                $set('filter_periode', null);
                                $set('tgl', null);
                                $set('deskripsi', null);
                                $set('items', []);
                            }),

                        Forms\Components\TextInput::make('filter_periode')
                            ->label('Periode')
                            ->type('month')
                            ->dehydrated(false)
                            ->live()
                            ->disabled(fn (Get $get) => blank($get('jenis_transaksi')))
                            ->placeholder('Pilih periode...')
                            ->afterStateUpdated(fn (Set $set) => $set('no_referensi', null)),

                        Select::make('no_referensi')
                            ->label('No Referensi')
                            ->options(function (Get $get): array {
                                $jenis   = $get('jenis_transaksi');
                                $periode = $get('filter_periode');
                                if (!$jenis || !$periode) return [];

                                [$tahun, $bulan] = explode('-', $periode);

                                $sudahAda = Jurnal::pluck('no_referensi')->toArray();

                                return match ($jenis) {
                                    'pemesanan' => Pemesanan::whereYear('tgl_pesan', $tahun)
                                        ->whereMonth('tgl_pesan', $bulan)
                                        ->whereNotIn('kode_pemesanan', $sudahAda)
                                        ->pluck('kode_pemesanan', 'kode_pemesanan')->toArray(),

                                    'pembelian' => Pembelian::whereYear('tanggal_beli', $tahun)
                                        ->whereMonth('tanggal_beli', $bulan)
                                        ->whereNotIn('nomor_faktur', $sudahAda)
                                        ->pluck('nomor_faktur', 'nomor_faktur')->toArray(),

                                    'biaya' => PencatatanBiaya::whereYear('tanggal_catat', $tahun)
                                        ->whereMonth('tanggal_catat', $bulan)
                                        ->get()
                                        ->filter(fn ($b) => !in_array((string) $b->id_pencatatan_beban, $sudahAda))
                                        ->mapWithKeys(fn ($b) => [
                                            (string) $b->id_pencatatan_beban =>
                                                ($b->jenis_beban ?? 'Biaya') . ' - ' . $b->id_pencatatan_beban
                                        ])->toArray(),

                                    default => [],
                                };
                            })
                            ->searchable()
                            ->live()
                            ->disabled(fn (Get $get) => blank($get('filter_periode')))
                            ->placeholder(fn (Get $get) => match(true) {
                                blank($get('jenis_transaksi')) => 'Pilih jenis transaksi dulu',
                                blank($get('filter_periode'))  => 'Pilih periode dulu',
                                default                        => 'Pilih referensi...',
                            })
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $jenis = $get('jenis_transaksi');
                                if (!$jenis || !$state) return;

                                match ($jenis) {
                                    'pemesanan' => (function () use ($state, $set) {
                                        $data = Pemesanan::where('kode_pemesanan', $state)->first();
                                        $set('tgl', $data?->tgl_pesan);
                                        $set('deskripsi', 'Pemesanan Laundry - ' . $state);
                                    })(),
                                    'pembelian' => (function () use ($state, $set) {
                                        $data = Pembelian::where('nomor_faktur', $state)->first();
                                        $set('tgl', $data?->tanggal_beli?->format('Y-m-d'));
                                        $set('deskripsi', 'Pembelian ' . ($data?->jenis_pembelian ?? '') . ' - ' . $state);
                                    })(),
                                    'biaya' => (function () use ($state, $set) {
                                        $data = PencatatanBiaya::find($state);
                                        $set('tgl', $data?->tanggal_catat);
                                        $set('deskripsi', ($data?->jenis_beban ?? 'Biaya') . ' - ' . $state);
                                    })(),
                                    default => null,
                                };

                                $details = self::getDetailOtomatis($jenis, $state);
                                $set('items', $details);
                            }),

                        DatePicker::make('tgl')
                            ->label('Tanggal')
                            ->required()
                            ->default(now()),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi'),
                    ])
                    ->columns(1)
                    ->collapsed()
                    ->collapsible(),

                Section::make('Detail Jurnal')
                    ->schema([
                        Repeater::make('items')
                            ->label('Detail Jurnal')
                            ->relationship('jurnaldetail')
                            ->schema([
                                Select::make('coa_id')
                                    ->label('Akun')
                                    ->options(Coa::all()->pluck('nama_akun', 'id'))
                                    ->searchable()
                                    ->required(),
                                TextInput::make('debit')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->required(),
                                TextInput::make('credit')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->required(),
                                Textarea::make('deskripsi')
                                    ->label('Keterangan')
                                    ->rows(2),
                            ])
                            ->columns(1)
                            ->required(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tgl')->date(),
                TextColumn::make('no_referensi')->label('Ref'),
                TextColumn::make('deskripsi')->limit(30),
                TextColumn::make('jurnaldetail.debit')
                    ->label('Total Debit')
                    ->formatStateUsing(function ($state, $record) {
                        $debit = $record->jurnaldetail()->sum('debit');
                        return 'Rp ' . number_format((float) $debit, 0, ',', '.');
                    })
                    ->alignment('end'),
                TextColumn::make('jurnaldetail.credit')
                    ->label('Total Kredit')
                    ->formatStateUsing(function ($state, $record) {
                        $credit = $record->jurnaldetail()->sum('credit');
                        return 'Rp ' . number_format((float) $credit, 0, ',', '.');
                    })
                    ->alignment('end'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tgl', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJurnals::route('/'),
            'create' => Pages\CreateJurnal::route('/create'),
            'edit'   => Pages\EditJurnal::route('/{record}/edit'),
        ];
    }
}