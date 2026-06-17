<?php

namespace App\Filament\Resources\AnalisisCashflowResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CashflowDoughnutChart extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Cashflow Bulan Ini';
    protected int|string|array $columnSpan = '1';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $bulan = now()->month;
        $tahun = now()->year;

        $pemasukan = DB::table('jurnal_detail')
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
            ->where('jurnal_detail.coa_id', 1)
            ->where('jurnal_detail.debit', '>', 0)
            ->whereMonth('jurnal.tgl', $bulan)
            ->whereYear('jurnal.tgl', $tahun)
            ->sum('jurnal_detail.debit');

        $pengeluaran = DB::table('jurnal_detail')
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
            ->where('jurnal_detail.coa_id', 1)
            ->where('jurnal_detail.credit', '>', 0)
            ->whereMonth('jurnal.tgl', $bulan)
            ->whereYear('jurnal.tgl', $tahun)
            ->sum('jurnal_detail.credit');

        return [
            'datasets' => [
                [
                    'data' => [$pemasukan, $pengeluaran],
                    'backgroundColor' => ['#22c55e', '#ef4444'],
                ],
            ],
            'labels' => ['Pemasukan', 'Pengeluaran'],
        ];
    }
}