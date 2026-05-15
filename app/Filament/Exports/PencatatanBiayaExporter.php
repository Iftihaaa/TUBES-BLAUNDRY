<?php

namespace App\Filament\Exports;

use App\Models\PencatatanBiaya;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;

class PencatatanBiayaExporter extends Exporter
{
    protected static ?string $model = PencatatanBiaya::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id_pencatatan_beban')
                ->label('ID Pencatatan Biaya')
                ->state(fn (PencatatanBiaya $record) => $record->id_pencatatan_beban),
            ExportColumn::make('tanggal_catat')
                ->label('Tanggal Catat')
                ->formatStateUsing(fn ($state): string => $state ? \Carbon\Carbon::parse($state)->format('Y-m-d') : ''),
            ExportColumn::make('coa.nama_akun')
                ->label('Akun Beban')
                ->state(fn (PencatatanBiaya $record) => $record->coa?->nama_akun ?? ''),
            ExportColumn::make('pegawai.nama')
                ->label('Nama Pegawai')
                ->state(fn (PencatatanBiaya $record) => $record->pegawai?->nama ?? ''),
            ExportColumn::make('jenis_beban')
                ->label('Jenis Beban'),
            ExportColumn::make('nominal')
                ->label('Nominal Biaya')
                ->formatStateUsing(fn ($state): string => is_numeric($state) ? number_format($state, 0, ',', '.') : (string) $state),
            ExportColumn::make('keterangan')
                ->label('Keterangan')
                ->enabledByDefault(false),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            DatePicker::make('start_date')
                ->label('Tanggal Mulai')
                ->nullable(),
            DatePicker::make('end_date')
                ->label('Tanggal Sampai')
                ->nullable(),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export Pencatatan Biaya telah selesai dan ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }

    public function getJobConnection(): ?string
    {
        return 'sync';
    }
}