<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PenjualanPerBulanChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = '1';

    public function getHeading(): string
    {
        return 'Pendapatan Per Bulan ' . now()->year;
    }

    protected function getData(): array
    {
        $tahun = now()->year;

        $data = DB::table('pemesanan')
            ->selectRaw('MONTH(tgl_pesan) as bulan, SUM(total_harga) as total')
            ->whereYear('tgl_pesan', $tahun)
            ->groupBy(DB::raw('MONTH(tgl_pesan)'))
            ->orderBy('bulan')
            ->get();

        $bulanan = array_fill(1, 12, 0);
        foreach ($data as $row) {
            $bulanan[$row->bulan] = (float) $row->total;
        }

        $labelBulan = [
            1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',
            5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',
            9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des',
        ];

        return [
            'datasets' => [[
                'label'           => 'Total Pendapatan (Rp)',
                'data'            => array_values($bulanan),
                'borderColor'     => '#f59e0b',
                'backgroundColor' => 'rgba(245,158,11,0.15)',
                'fill'            => true,
                'tension'         => 0.4,
            ]],
            'labels' => array_values($labelBulan),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}