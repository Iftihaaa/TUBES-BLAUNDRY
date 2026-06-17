<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PenjualanPerLayananChart extends ChartWidget
{
    protected static ?string $heading = 'Transaksi Per Layanan';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = '1';
    protected function getData(): array
    {
        $data = DB::table('detail_pemesanan')
            ->selectRaw('nama_layanan, COUNT(*) as jumlah')
            ->groupBy('nama_layanan')
            ->orderByDesc('jumlah')
            ->get();

        $labels = $data->pluck('nama_layanan')->toArray();
        $values = $data->pluck('jumlah')->toArray();

        $warna = [
            'rgba(99,102,241,0.8)',
            'rgba(16,185,129,0.8)',
            'rgba(245,158,11,0.8)',
            'rgba(239,68,68,0.8)',
            'rgba(139,92,246,0.8)',
        ];

        return [
            'datasets' => [[
                'label'           => 'Jumlah Transaksi',
                'data'            => $values,
                'backgroundColor' => array_slice($warna, 0, count($labels)),
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}