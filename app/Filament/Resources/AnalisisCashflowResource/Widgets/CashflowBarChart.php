<?php

namespace App\Filament\Resources\AnalisisCashflowResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CashflowBarChart extends ChartWidget
{
    protected static ?string $heading = 'Cashflow 6 Bulan Terakhir';
    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        $pemasukan = [];
        $pengeluaran = [];

        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $labels[] = $bulan->format('M Y');

            $masuk = DB::table('jurnal_detail')
                ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
                ->where('jurnal_detail.coa_id', 1)
                ->where('jurnal_detail.debit', '>', 0)
                ->whereMonth('jurnal.tgl', $bulan->month)
                ->whereYear('jurnal.tgl', $bulan->year)
                ->sum('jurnal_detail.debit');

            $keluar = DB::table('jurnal_detail')
                ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
                ->where('jurnal_detail.coa_id', 1)
                ->where('jurnal_detail.credit', '>', 0)
                ->whereMonth('jurnal.tgl', $bulan->month)
                ->whereYear('jurnal.tgl', $bulan->year)
                ->sum('jurnal_detail.credit');

            $pemasukan[] = $masuk;
            $pengeluaran[] = $keluar;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $pemasukan,
                    'backgroundColor' => '#22c55e',
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $pengeluaran,
                    'backgroundColor' => '#ef4444',
                ],
            ],
            'labels' => $labels,
        ];
    }
}