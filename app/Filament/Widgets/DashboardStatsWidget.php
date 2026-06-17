<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalPelanggan = DB::table('members')->count();

        $totalTransaksi = DB::table('pemesanan')->count();

        // $totalPendapatan = DB::table('pemesanan')
        //     ->where('status', 'done')
        //     ->sum('total_harga');
        $totalPendapatan = DB::table('pemesanan')
            ->sum('total_harga');    

        $totalPengeluaran = DB::table('pencatatan_biaya')
            ->sum('nominal');
        // $totalPengeluaran = 0; // sementara

        $keuntungan = $totalPendapatan - $totalPengeluaran;

        return [
            Stat::make('Total Pelanggan', $totalPelanggan)
                ->description('Jumlah pelanggan terdaftar')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('Total Transaksi', $totalTransaksi)
                ->description('Jumlah transaksi')
                ->icon('heroicon-o-shopping-bag')
                ->color('warning'),

            Stat::make('Total Pendapatan', 'Rp ' . number_format($totalPendapatan, 0, ',', '.'))
                ->description('Jumlah transaksi terbayar')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Total Keuntungan', 'Rp ' . number_format($keuntungan, 0, ',', '.'))
                ->description('Pendapatan - Pengeluaran')
                ->icon('heroicon-o-chart-bar')
                ->color('primary'),
        ];
    }
}