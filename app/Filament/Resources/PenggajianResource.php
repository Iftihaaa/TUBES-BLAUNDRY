<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenggajianResource\Pages;
use App\Mail\SlipGajiMail;
use App\Models\Penggajian;
use App\Models\Pegawai;
use App\Models\Absensi;

use Barryvdh\DomPDF\Facade\Pdf;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;

use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Table;

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
    {
        return $form
            ->schema([

                Forms\Components\Wizard::make([

                    /*
                    |--------------------------------------------------------------------------
                    | STEP 1
                    |--------------------------------------------------------------------------
                    */

                    Forms\Components\Wizard\Step::make('Data Pegawai')

                        ->columns(2)

                        ->schema([
                            Forms\Components\TextInput::make('id_penggajian')

                                ->label('ID Penggajian')

                                ->default(function () {

                                    $last = Penggajian::latest()->first();

                                    if (!$last) {
                                        return 'PGJ-1';
                                    }

                                    $number = (int) str_replace(
                                        'PGJ-',
                                        '',
                                        $last->id_penggajian
                                    );

                                    return 'PGJ-' . ($number + 1);
                                })

                                ->readOnly(),

                            Forms\Components\Select::make('id_pegawai')

                                ->label('Nama Pegawai')

                                ->searchable()

                                ->preload()

                                ->required()

                                ->options(
                                    Pegawai::orderBy('nama', 'asc')
                                        ->pluck('nama', 'id_pegawai')
                                )

                                ->reactive()

                                ->afterStateUpdated(function ($state, Set $set) {
                                    $pegawai = Pegawai::find($state);

                                    // JUMLAH HADIR
                                    $jumlahHadir = Absensi::where(
                                        'id_pegawai',
                                        $state
                                    )

                                    ->where('kehadiran', 'Hadir')

                                    ->count();

                                    // JUMLAH TIDAK HADIR
                                    $jumlahTidakHadir = Absensi::where(
                                        'id_pegawai',
                                        $state
                                    )

                                    ->where('kehadiran', '!=', 'Hadir')

                                    ->count();

                                    // GAJI POKOK
                                    $gajiPokok = $pegawai->gaji_pokok ?? 0;

                                    // SET DATA
                                    $set('jumlah_hadir', $jumlahHadir);

                                    $set('jumlah_tidak_hadir', $jumlahTidakHadir);

                                    $set('gaji_pokok', $gajiPokok);

                                    $set('nominal_bonus', 0);

                                    // POTONGAN
                                    $potongan = $jumlahTidakHadir * 5000;

                                    // TOTAL
                                    $total = $gajiPokok - $potongan;

                                    // JIKA MINUS
                                    if ($total < 0) {
                                        $total = 0;
                                    }

                                    // TOTAL GAJI
                                    $set('total_gaji', $total);

                                }),

                            Forms\Components\DatePicker::make('tanggal_bayar')

                                ->label('Tanggal Bayar')

                                ->default(now())

                                ->required(),

                        ]),

                    /*
                    |--------------------------------------------------------------------------
                    | STEP 2
                    |--------------------------------------------------------------------------
                    */

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

                                ->afterStateUpdated(function (
                                    $state,
                                    Get $get,
                                    Set $set
                                ) {

                                    $gajiPokok = (float) (
                                        $get('gaji_pokok') ?? 0
                                    );

                                    // POTONGAN 5 RIBU
                                    $potongan = $state * 5000;

                                    // TOTAL
                                    $total = $gajiPokok - $potongan;

                                    // JIKA MINUS
                                    if ($total < 0) {
                                        $total = 0;
                                    }

                                    $set('total_gaji', $total);
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

                    /*
                    |--------------------------------------------------------------------------
                    | STEP 3
                    |--------------------------------------------------------------------------
                    */

                    Forms\Components\Wizard\Step::make('Bonus')

                        ->columns(2)

                        ->schema([

                            Forms\Components\Select::make('dapat_bonus')

                                ->label('Bonus')

                                ->options([
                                    'ya' => 'Dapat Bonus',
                                    'tidak' => 'Tidak Dapat Bonus',
                                ])

                                ->default('tidak')

                                ->reactive()

                                ->afterStateUpdated(function (
                                    $state,
                                    Get $get,
                                    Set $set
                                ) {

                                    $totalGaji = (float) (
                                        $get('total_gaji') ?? 0
                                    );

                                    $bonus = 0;

                                    // BONUS 10%
                                    if ($state == 'ya') {

                                        $bonus = $totalGaji * 0.10;
                                    }

                                    $set('nominal_bonus', $bonus);

                                    $set(
                                        'total_gaji',
                                        $totalGaji + $bonus
                                    );
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

                    /*
                    |--------------------------------------------------------------------------
                    | STEP 4
                    |--------------------------------------------------------------------------
                    */

                    Forms\Components\Wizard\Step::make('Total Gaji')

                        ->schema([

                            Forms\Components\Placeholder::make('review')

                                ->label('Total Gaji')

                                ->content(function ($get) {

                                    return 'IDR ' .

                                        number_format(
                                            $get('total_gaji'),
                                            0,
                                            ',',
                                            '.'
                                        );
                                }),

                            Forms\Components\Actions::make([

                                /*
                                |--------------------------------------------------------------------------
                                | PDF
                                |--------------------------------------------------------------------------
                                */

                                Forms\Components\Actions\Action::make('download_pdf')

                                    ->label('Unduh Slip Gaji PDF')

                                    ->icon('heroicon-o-document-arrow-down')

                                    ->color('success')

                                    ->action(function (Get $get) {

                                        $pegawai = Pegawai::find(
                                            $get('id_pegawai')
                                        );

                                        $penggajian = new Penggajian([

                                            'id_penggajian' =>
                                                $get('id_penggajian'),

                                            'tanggal_bayar' =>
                                                Carbon::parse(
                                                    $get('tanggal_bayar')
                                                ),

                                            'jumlah_hadir' =>
                                                $get('jumlah_hadir'),

                                            'jumlah_tidak_hadir' =>
                                                $get('jumlah_tidak_hadir'),

                                            'gaji_pokok' =>
                                                $get('gaji_pokok'),

                                            'nominal_bonus' =>
                                                $get('nominal_bonus'),

                                            'total_gaji' =>
                                                $get('total_gaji'),
                                        ]);

                                        $penggajian->setRelation(
                                            'pegawai',
                                            $pegawai
                                        );

                                        $pdf = Pdf::loadView(
                                            'pdf.slip_gaji',
                                            [
                                                'penggajian' => $penggajian
                                            ]
                                        );

                                        return response()->streamDownload(
                                            fn () => print($pdf->output()),
                                            'slip-gaji.pdf'
                                        );
                                    }),

                            ]),

                        ]),

                ])

                /*
                |--------------------------------------------------------------------------
                | TOMBOL CREATE — hanya muncul di halaman Create, tidak di Edit
                |--------------------------------------------------------------------------
                */

                ->submitAction(new \Illuminate\Support\HtmlString(

                    request()->routeIs('filament.admin.resources.penggajians.create')

                        ? '<button
                                type="submit"
                                class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                                + Create
                            </button>'

                        : ''

                ))

                ->columnSpanFull(),

            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return $table

            ->recordUrl(null)

            ->columns([

                Tables\Columns\TextColumn::make('id_penggajian')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pegawai.nama')
                    ->label('Nama Pegawai')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tanggal_bayar')
                    ->date(),

                Tables\Columns\TextColumn::make('jumlah_hadir'),

                Tables\Columns\TextColumn::make('jumlah_tidak_hadir'),

                Tables\Columns\TextColumn::make('gaji_pokok')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('nominal_bonus')
                    ->label('Bonus')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('total_gaji')
                    ->money('IDR'),

                /*
                |--------------------------------------------------------------------------
                | BADGE — sudah dibayar (hijau) & belum dibayar (kuning)
                |--------------------------------------------------------------------------
                */

                Tables\Columns\BadgeColumn::make('status_pembayaran')

                    ->colors([
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

            'index' => Pages\ListPenggajians::route('/'),

            'create' => Pages\CreatePenggajian::route('/create'),

            'edit' => Pages\EditPenggajian::route('/{record}/edit'),

        ];
    }
}