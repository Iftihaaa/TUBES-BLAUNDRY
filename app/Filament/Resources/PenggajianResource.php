<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PenggajianExport;
use App\Filament\Resources\PenggajianResource\Pages;
use App\Mail\SlipGajiMail;
use App\Models\Penggajian;
use App\Models\Pegawai;
use App\Models\Absensi;

use Filament\Notifications\Notification;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;

use Filament\Forms\Set;

use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class PenggajianResource extends Resource
{
    protected static ?string $model = Penggajian::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Penggajian';
    
    protected static ?string $modelLabel = 'Penggajian';
    
    protected static ?string $pluralModelLabel = 'Penggajian';
    
    protected static ?string $navigationGroup = 'Transaksi';

    public static function form(Form $form): Form
    //Ini yang mengatur tampilan di sidebar kiri Filament.
    // navigationIcon = ikon uang, 
    // navigationLabel = teks 'Penggajian', 
    // navigationGroup = dikelompokkan di bawah 'Transaksi'.
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([

                    // STEP 1
                    Forms\Components\Wizard\Step::make('Data Pegawai')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('id_penggajian')
                                ->label('ID Penggajian')
                                ->default(function () {
                                    $last = Penggajian::latest()->first();
                                    if (!$last) return 'PGJ-1';
                                    $number = (int) str_replace('PGJ-', '', $last->id_penggajian);
                                    return 'PGJ-' . ($number + 1);
                                })
                                ->readOnly(),

                            Forms\Components\Select::make('id_pegawai')
                                ->label('Nama Pegawai')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->options(Pegawai::orderBy('nama', 'asc')->pluck('nama', 'id_pegawai'))
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    $pegawai = Pegawai::find($state);
                                    
                                    //Saat admin memilih nama pegawai, sistem otomatis menghitung:
                                    $jumlahHadir = Absensi::where('id_pegawai', $state)->where('kehadiran', 'Hadir')->count();
                                    $jumlahTidakHadir = Absensi::where('id_pegawai', $state)->where('kehadiran', '!=', 'Hadir')->count();
                                    $gajiPokok = $pegawai->gaji_pokok ?? 0;

                                    $set('jumlah_hadir', $jumlahHadir);
                                    $set('jumlah_tidak_hadir', $jumlahTidakHadir);
                                    $set('gaji_pokok', $gajiPokok);
                                    $set('nominal_bonus', 0);

                                    $potongan = $jumlahTidakHadir * 5000;
                                    $total = $gajiPokok - $potongan;
                                    if ($total < 0) $total = 0;
                                    $set('total_gaji', $total);
                                }),

                            Forms\Components\DatePicker::make('tanggal_bayar')
                                ->label('Tanggal Bayar')
                                ->default(now())
                                ->required(),
                        ]),

                    // STEP 2
                    Forms\Components\Wizard\Step::make('Absensi')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('jumlah_hadir')
                                ->numeric()
                                ->readOnly(),

                            Forms\Components\TextInput::make('jumlah_tidak_hadir')
                                ->label('Jumlah Tidak Hadir')
                                ->numeric()
                                ->default(0)
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $gajiPokok = (float) ($get('gaji_pokok') ?? 0);
                                    $potongan = $state * 5000;
                                    $total = $gajiPokok - $potongan;
                                    if ($total < 0) $total = 0;
                                    $set('total_gaji', $total);
                                    //Saat admin memilih nama pegawai, sistem otomatis menghitung
                                }),

                            Forms\Components\TextInput::make('gaji_pokok')
                                ->label('Gaji Pokok')
                                ->prefix('IDR')
                                ->numeric()
                                ->readOnly(),

                            Forms\Components\TextInput::make('total_gaji')
                                ->label('Total Gaji')
                                ->prefix('IDR')
                                ->numeric()
                                ->readOnly(),
                        ]),

                    // STEP 3
                    Forms\Components\Wizard\Step::make('Bonus')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('dapat_bonus')
                                ->label('Bonus')
                                ->options(['ya' => 'Dapat Bonus', 'tidak' => 'Tidak Dapat Bonus'])
                                ->default('tidak')
                                ->reactive()
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $totalGaji = (float) ($get('total_gaji') ?? 0);
                                    $bonus = $state == 'ya' ? $totalGaji * 0.10 : 0;
                                    $set('nominal_bonus', $bonus);
                                    $set('total_gaji', $totalGaji + $bonus);
                                }),

                            Forms\Components\TextInput::make('nominal_bonus')
                                ->label('Nominal Bonus')
                                ->prefix('IDR')
                                ->numeric()
                                ->readOnly(),

                            Forms\Components\TextInput::make('total_gaji')
                                ->label('Total Gaji')
                                ->prefix('IDR')
                                ->numeric()
                                ->readOnly(),
                        ]),

                    // STEP 4
                    Forms\Components\Wizard\Step::make('Total Gaji')
                        ->schema([
                            Forms\Components\Placeholder::make('review')
                                ->label('Total Gaji')
                                ->content(fn ($get) => 'IDR ' . number_format($get('total_gaji'), 0, ',', '.')),

                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('download_pdf')
                                    ->label('Unduh Slip Gaji PDF')
                                    ->icon('heroicon-o-document-arrow-down')
                                    ->color('success')
                                    ->action(function (Get $get) {
                                        $pegawai = Pegawai::find($get('id_pegawai'));
                                        $penggajian = new Penggajian([
                                            'id_penggajian'    => $get('id_penggajian'),
                                            'tanggal_bayar'    => Carbon::parse($get('tanggal_bayar')),
                                            'jumlah_hadir'     => $get('jumlah_hadir'),
                                            'jumlah_tidak_hadir' => $get('jumlah_tidak_hadir'),
                                            'gaji_pokok'       => $get('gaji_pokok'),
                                            'nominal_bonus'    => $get('nominal_bonus'),
                                            'total_gaji'       => $get('total_gaji'),
                                        ]);
                                        $penggajian->setRelation('pegawai', $pegawai);
                                        $pdf = Pdf::loadView('pdf.slip_gaji', ['penggajian' => $penggajian]);
                                        return response()->streamDownload(fn () => print($pdf->output()), 'slip-gaji.pdf');
                                    }),
                            ]),
                        ]),

                ])

                ->submitAction(new \Illuminate\Support\HtmlString(
                    request()->routeIs(
                        'filament.admin.resources.penggajians.create'
                    )

                    ? new \Illuminate\Support\HtmlString(

                        '<button
                            type="submit"
                            class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">

                            + Create

                        </button>'

                    )

                    : null
                ))

                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->headerActions([
                Action::make('export_excel') //Tombol Export Excel di pojok kanan atas tabel. Saat diklik, memanggil PenggajianExport.php untuk generate file Excel.
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn () => Excel::download(new PenggajianExport(), 'penggajian-' . now()->format('d-m-Y') . '.xlsx')),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id_penggajian')->searchable(),
                Tables\Columns\TextColumn::make('pegawai.nama')->label('Nama Pegawai')->searchable(),
                Tables\Columns\TextColumn::make('tanggal_bayar')->date(),
                Tables\Columns\TextColumn::make('jumlah_hadir'),
                Tables\Columns\TextColumn::make('jumlah_tidak_hadir'),
                Tables\Columns\TextColumn::make('gaji_pokok')->money('IDR'),
                Tables\Columns\TextColumn::make('nominal_bonus')->label('Bonus')->money('IDR'),
                Tables\Columns\TextColumn::make('total_gaji')->money('IDR'),
                Tables\Columns\BadgeColumn::make('status_pembayaran')
                    ->colors([  //Status pembayaran tampil sebagai badge berwarna. 
                                // Hijau = sudah dibayar, 
                                // Kuning = belum dibayar.
                        'success' => 'sudah dibayar',
                        'warning' => 'belum dibayar',
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPenggajians::route('/'),
            'create' => Pages\CreatePenggajian::route('/create'),
            'edit'   => Pages\EditPenggajian::route('/{record}/edit'),
        ];
    }
}