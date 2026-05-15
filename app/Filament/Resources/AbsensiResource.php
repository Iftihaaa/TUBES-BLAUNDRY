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

                Forms\Components\Select::make('id_pegawai')
                    ->label('Pegawai')
                    ->relationship('pegawai', 'nama')
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('kehadiran')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                    ])
                    ->required(),

                Forms\Components\FileUpload::make('upload_bukti')
                    ->label('Upload Bukti')
                    ->directory('bukti-absensi')
                    ->disk('public')
                    ->image()
                    ->imagePreviewHeight('150')
                    ->downloadable()
                    ->openable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id_absen')

            ->columns([

                Tables\Columns\TextColumn::make('id_absen')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pegawai.nama')
                    ->label('Nama Pegawai')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('kehadiran')
                    ->colors([
                        'success' => 'Hadir',
                        'warning' => 'Izin',
                        'danger' => 'Sakit',
                    ]),

                Tables\Columns\ImageColumn::make('upload_bukti')
                    ->label('Bukti')
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y'),
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

                        Forms\Components\Select::make('id_pegawai')
                            ->label('Pegawai')
                            ->options(
                                \App\Models\Pegawai::pluck('nama', 'id_pegawai')
                            )
                            ->searchable()
                            ->required(),
                    ])

                    ->action(function (array $data) {

                        $bulan = $data['bulan'];
                        $idPegawai = $data['id_pegawai'];

                        $absensis = Absensi::with('pegawai')
                            ->where('id_pegawai', $idPegawai)
                            ->whereMonth('created_at', \Carbon\Carbon::parse($bulan)->month)
                            ->whereYear('created_at', \Carbon\Carbon::parse($bulan)->year)
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbsensis::route('/'),
            'create' => Pages\CreateAbsensi::route('/create'),
            'edit' => Pages\EditAbsensi::route('/{record}/edit'),
        ];
    }
}