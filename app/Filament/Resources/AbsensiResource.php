<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbsensiResource\Pages;
use App\Models\Absensi;

use Barryvdh\DomPDF\Facade\Pdf;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Absensi';

    protected static ?string $pluralLabel = 'Absensi';

    protected static ?string $navigationGroup = 'Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('pegawai_id')
                    ->label('Pegawai')
                    ->relationship('pegawai', 'nama')
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('tanggal')
                    ->required(),

                Forms\Components\TimePicker::make('jam_masuk')
                    ->label('Jam Masuk'),

                Forms\Components\TimePicker::make('jam_keluar')
                    ->label('Jam Keluar'),

                Forms\Components\Select::make('status')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpha' => 'Alpha',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pegawai.nama')
                    ->label('Nama Pegawai')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jam_masuk')
                    ->label('Jam Masuk'),

                Tables\Columns\TextColumn::make('jam_keluar')
                    ->label('Jam Keluar'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'hadir',
                        'warning' => 'izin',
                        'danger' => 'sakit',
                        'secondary' => 'alpha',
                    ]),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan'),
            ])

            ->headerActions([
                Action::make('downloadPdf')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('bulan')
                            ->type('month')
                            ->required(),

                        Forms\Components\Select::make('pegawai_id')
                            ->label('Pegawai')
                            ->options(
                                \App\Models\Pegawai::pluck('nama', 'id_pegawai')
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $bulan = $data['bulan'];
                        $pegawaiId = $data['pegawai_id'];

                        $absensis = Absensi::with('pegawai')
                            ->where('pegawai_id', $pegawaiId)
                            ->whereMonth('tanggal', \Carbon\Carbon::parse($bulan)->month)
                            ->whereYear('tanggal', \Carbon\Carbon::parse($bulan)->year)
                            ->get();

                        $pdf = Pdf::loadView('pdf.absensi', [
                            'absensis' => $absensis,
                            'bulan' => $bulan,
                        ]);

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'rekap-absensi.pdf'
                        );
                    }),
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbsensis::route('/'),
            'create' => Pages\CreateAbsensi::route('/create'),
            'edit' => Pages\EditAbsensi::route('/{record}/edit'),
        ];
    }
}