<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PencatatanBiayaResource\Pages;
use App\Models\PencatatanBiaya;
use App\Models\AkunCoa;
use App\Models\Penggajian;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

use Barryvdh\DomPDF\Facade\Pdf; //pdf

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;

use Filament\Tables\Actions\ExportAction; //tombol export excel
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Actions\Action;

use App\Filament\Exports\PencatatanBiayaExporter;

class PencatatanBiayaResource extends Resource
{
    protected static ?string $model = PencatatanBiaya::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pencatatan Biaya';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $modelLabel = 'Pencatatan Biaya';

    protected static ?string $pluralModelLabel = 'Pencatatan Biaya';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([

                    // STEP 1
                    Wizard\Step::make('Tanggal Pencatatan')
                        ->schema([
                            DatePicker::make('tanggal_catat')
                                ->label('Tanggal Catat')
                                ->required(),
                        ]),

                    // STEP 2
                    Wizard\Step::make('Identitas Pegawai')
                        ->schema([
                            Select::make('id_pegawai')
                                ->label('Nama Pegawai yang Mencatat')
                                ->relationship('pegawai', 'nama')
                                ->required()
                                ->searchable()
                                ->preload(),
                        ]),

                    // STEP 3
                    Wizard\Step::make('Lengkapi Detail Biaya')
                        ->schema([

                            Select::make('id_coa')
                                ->label('Pilih Akun Beban')
                                ->relationship(
                                    name: 'coa',
                                    titleAttribute: 'nama_akun',
                                    modifyQueryUsing: fn (Builder $query) =>
                                        $query->where('header_akun', 5),
                                )
                                ->required()
                                ->searchable()
                                ->preload()
                                ->reactive() //Field berubah otomatis tanpa reload halaman.

                                ->afterStateUpdated(function ($state, Forms\Set $set) { //Menjalankan proses setelah field berubah.

                                    $akun = AkunCoa::find($state);

                                    if ($akun) {
                                        $set('jenis_beban', $akun->nama_akun);
                                    }

                                    $set('bulan_penggajian', null);
                                    $set('nominal', null);
                                })

                                ->afterStateHydrated(function ($state, Forms\Set $set) {

                                    $akun = AkunCoa::find($state);

                                    if ($akun) {
                                        $set('jenis_beban', $akun->nama_akun);
                                    }
                                }),

                            // BEBAN GAJI
                            Select::make('bulan_penggajian')
                                ->label('Pilih Periode Penggajian')
                                ->placeholder('Pilih bulan penggajian...')

                                ->visible(fn (Forms\Get $get) =>
                                    optional(AkunCoa::find($get('id_coa')))->nama_akun === 'Beban Gaji')

                                ->required(fn (Forms\Get $get) =>
                                    optional(AkunCoa::find($get('id_coa')))->nama_akun === 'Beban Gaji')

                                ->reactive()

                                ->options(function () {

                                    return Penggajian::whereNotNull('tanggal_bayar')
                                        ->get()

                                        ->groupBy(fn ($item) =>
                                            Carbon::parse($item->tanggal_bayar)->format('Y-m'))

                                        ->map(function ($items, $yearMonth) {

                                            $total = $items->sum('total_gaji');

                                            $label = Carbon::parse($yearMonth . '-01')
                                                ->translatedFormat('F Y');

                                            $formatted = 'Rp ' . number_format($total, 0, ',', '.');

                                            return $label . ' — Total: ' . $formatted;
                                        })

                                        ->toArray();
                                })

                                ->afterStateUpdated(function ($state, Forms\Set $set) {

                                    if ($state) {

                                        [$year, $month] = explode('-', $state);

                                        $total = Penggajian::whereNotNull('tanggal_bayar')
                                            ->whereYear('tanggal_bayar', $year)
                                            ->whereMonth('tanggal_bayar', $month)
                                            ->sum('total_gaji');

                                        $set('nominal', $total);
                                    }
                                }),

                            // BEBAN LAIN-LAIN
                            TextInput::make('keterangan_beban_manual')
                                ->label('Nama Beban Manual')
                                ->placeholder('Contoh: Biaya Servis Mesin')

                                ->required(fn (Forms\Get $get) =>
                                    optional(AkunCoa::find($get('id_coa')))->nama_akun === 'Beban lain-lain')

                                ->visible(fn (Forms\Get $get) =>
                                    optional(AkunCoa::find($get('id_coa')))->nama_akun === 'Beban lain-lain')

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
                                ->required()

                                ->readOnly(fn (Forms\Get $get) =>
                                    optional(AkunCoa::find($get('id_coa')))->nama_akun === 'Beban Gaji'),

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

                Tables\Columns\TextColumn::make('tanggal_catat')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pegawai.nama')
                    ->label('Pegawai')
                    ->searchable(),

                Tables\Columns\TextColumn::make('jenis_beban')
                    ->label('Jenis Beban')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bulan_penggajian')
                    ->label('Periode Gaji')

                    ->formatStateUsing(fn ($state) =>
                        $state
                            ? Carbon::parse($state . '-01')->translatedFormat('F Y')
                            : '-')

                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->headerActions([

                // EXPORT EXCEL
                ExportAction::make()
                    ->label('Export Excel Pencatatan Biaya')
                    ->exporter(PencatatanBiayaExporter::class)
                    ->color('success'),

                // EXPORT PDF
                Action::make('export_pdf')

                    ->label('Export PDF')

                    ->icon('heroicon-o-document-arrow-down')

                    ->color('success')

                    ->form([

                        Select::make('bulan')
                            ->label('Pilih Bulan')

                            ->options([
                                '01' => 'Januari',
                                '02' => 'Februari',
                                '03' => 'Maret',
                                '04' => 'April',
                                '05' => 'Mei',
                                '06' => 'Juni',
                                '07' => 'Juli',
                                '08' => 'Agustus',
                                '09' => 'September',
                                '10' => 'Oktober',
                                '11' => 'November',
                                '12' => 'Desember',
                            ])

                            ->required(),

                        TextInput::make('tahun')
                            ->label('Tahun')
                            ->numeric()
                            ->default(date('Y'))
                            ->required(),

                    ])

                    ->action(function (array $data) {

                        $biayas = PencatatanBiaya::whereMonth(
                                'tanggal_catat',
                                $data['bulan']
                            )

                            ->whereYear(
                                'tanggal_catat',
                                $data['tahun']
                            )

                            ->with(['pegawai', 'coa'])

                            ->get();

                        $pdf = Pdf::loadView(
                            'pdf.pencatatan_biaya',
                            [

                                'biayas' => $biayas,

                                'total' => $biayas->sum('nominal'),

                                'periode' => Carbon::createFromDate(
                                    $data['tahun'],
                                    $data['bulan'],
                                    1
                                )->translatedFormat('F Y'),

                            ]
                        );

                        return response()->streamDownload(

                            fn () => print($pdf->output()),

                            'laporan-biaya-' .
                            $data['bulan'] .
                            '-' .
                            $data['tahun'] .
                            '.pdf'
                        );
                    }),

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