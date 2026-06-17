<?php

namespace App\Filament\Exports;

use App\Models\Penggajian;
use Carbon\Carbon;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;

class PenggajianExport implements
    FromArray,
    ShouldAutoSize,
    WithStyles
{
    public function array(): array
    {
        $bulanTahun = strtoupper(
            Carbon::now()->translatedFormat('F-Y')
        );

        $rows = [];

        // BARIS 1 — JUDUL
        $rows[] = [
            'LIST PENGGAJIAN B\'LAUNDRY PERIODE ' . $bulanTahun,
            '', '', '', '', '', '', '', '',
        ];

        // BARIS 2 — HEADER KOLOM
        $rows[] = [
            'ID Penggajian',
            'Nama Pegawai',
            'Tanggal Bayar',
            'Jumlah Hadir',
            'Jumlah Tidak Hadir',
            'Gaji Pokok',
            'Bonus',
            'Total Gaji',
            'Status Pembayaran',
        ];

        // BARIS 3+ — DATA
        $data = Penggajian::with('pegawai')->get();

        foreach ($data as $row) {
            $rows[] = [
                $row->id_penggajian,
                $row->pegawai->nama ?? '-',
                Carbon::parse($row->tanggal_bayar)->format('d-m-Y'),
                $row->jumlah_hadir,
                $row->jumlah_tidak_hadir,
                'Rp ' . number_format($row->gaji_pokok, 0, ',', '.'),
                'Rp ' . number_format($row->nominal_bonus ?? 0, 0, ',', '.'),
                'Rp ' . number_format($row->total_gaji, 0, ',', '.'),
                $row->status_pembayaran,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // STYLING BARIS 1 — JUDUL
        $sheet->mergeCells('A1:I1');

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // STYLING BARIS 2 — HEADER KOLOM
        $sheet->getStyle('A2:I2')->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 12,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F4B183'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // STYLING DATA
        $sheet->getStyle('A3:I100')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        return [];
    }
}