<?php

namespace App\Filament\Exports; // lokasi file exporter

use App\Models\PencatatanBiaya; // import model pencatatan biaya
use Filament\Actions\Exports\ExportColumn; // membuat kolom excel
use Filament\Actions\Exports\Exporter; // class export filament
use Filament\Actions\Exports\Models\Export; // data proses export
use Filament\Forms\Components\DatePicker; // filter tanggal

class PencatatanBiayaExporter extends Exporter
{
    protected static ?string $model = PencatatanBiaya::class; // model yang diexport

    public static function getColumns(): array // kolom excel
    {
        return [

            ExportColumn::make('id_pencatatan_beban') // kolom id
                ->label('ID Pencatatan Biaya') // nama header excel
                ->state(fn (PencatatanBiaya $record) => $record->id_pencatatan_beban), // ambil id

            ExportColumn::make('tanggal_catat') // kolom tanggal
                ->label('Tanggal Catat')
                ->formatStateUsing(fn ($state): string => $state ? \Carbon\Carbon::parse($state)->format('Y-m-d') : ''), // format tanggal

            ExportColumn::make('coa.nama_akun') // relasi akun coa
                ->label('Akun Beban')
                ->state(fn (PencatatanBiaya $record) => $record->coa?->nama_akun ?? ''), // ambil nama akun

            ExportColumn::make('pegawai.nama') // relasi pegawai
                ->label('Nama Pegawai')
                ->state(fn (PencatatanBiaya $record) => $record->pegawai?->nama ?? ''), // ambil nama pegawai

            ExportColumn::make('jenis_beban') // kolom jenis beban
                ->label('Jenis Beban'),

            ExportColumn::make('nominal') // kolom nominal
                ->label('Nominal Biaya')
                ->formatStateUsing(fn ($state): string => is_numeric($state) ? number_format($state, 0, ',', '.') : (string) $state), // format rupiah

            ExportColumn::make('keterangan') // kolom keterangan
                ->label('Keterangan')
                ->enabledByDefault(false), // default tidak tampil
        ];
    }

    public static function getOptionsFormComponents(): array // form filter export
    {
        return [

            DatePicker::make('start_date') // tanggal mulai
                ->label('Tanggal Mulai')
                ->nullable(),

            DatePicker::make('end_date') // tanggal akhir
                ->label('Tanggal Sampai')
                ->nullable(),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string // notifikasi export
    {
        $body = 'Export Pencatatan Biaya telah selesai dan ' . number_format($export->successful_rows) . ' baris berhasil diekspor.'; // jumlah berhasil

        if ($failedRowsCount = $export->getFailedRowsCount()) { // cek gagal export
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.'; // jumlah gagal
        }

        return $body; // tampilkan notifikasi
    }

    public function getJobConnection(): ?string // proses export
    {
        return 'sync'; // dijalankan langsung
    }
}