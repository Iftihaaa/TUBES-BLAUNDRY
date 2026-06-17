<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ProyeksiKas30HariChart extends ChartWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = '1';
    // protected int | string | array $columnSpan = 'full';

    // Simpan hasil AI agar tidak request berulang
    // protected static ?array $cachedData = null;
    // protected static ?string $aiSummary = null;
    protected static ?string $aiSummary = null;

    public function getHeading(): string
    {
        return 'Proyeksi Arus Kas 30 Hari - Powered by Gemini AI';
    }

    public function getDescription(): ?string
    {
        // Tampilkan ringkasan analisis AI di bawah judul grafik
        return static::$aiSummary ?? 'Menganalisis data dengan Gemini AI...';
    }

    protected function getData(): array
    {
        $tgl_mulai = Carbon::now()->subDays(30);

        // Ambil data historis dari database
        $totalPemasukan = DB::table('pemesanan')
            ->where('tgl_pesan', '>=', $tgl_mulai)
            ->sum('total_harga') ?? 0;

        $totalPengeluaran = DB::table('pencatatan_biaya')
            ->where('created_at', '>=', $tgl_mulai)
            ->sum('nominal') ?? 0;

        $pendapatanHarian = round($totalPemasukan / 30);
        $pengeluaranHarian = round($totalPengeluaran / 30);
        $saldoAwal = max($totalPemasukan / 30 * 7, 1000000);

        // Kirim data ke Gemini API
        $proyeksi = $this->getProyeksiDariGemini(
            $pendapatanHarian,
            $pengeluaranHarian,
            $saldoAwal
        );

        $titikHari = [1, 5, 10, 15, 20, 25, 30];
        $labelHari = array_map(fn($h) => 'H' . $h, $titikHari);

        return [
            'datasets' => [
                [
                    'label'       => '📈 Optimis',
                    'data'        => array_map(fn($h) => $proyeksi['optimis'][$h] ?? 0, $titikHari),
                    'borderColor' => '#16a34a',
                    'borderDash'  => [6, 3],
                    'fill'        => false,
                    'tension'     => 0.3,
                    'pointRadius' => 4,
                ],
                [
                    'label'       => '➡️ Realistis',
                    'data'        => array_map(fn($h) => $proyeksi['realistis'][$h] ?? 0, $titikHari),
                    'borderColor' => '#4f46e5',
                    'fill'        => false,
                    'tension'     => 0.3,
                    'pointRadius' => 4,
                ],
                [
                    'label'       => '📉 Pesimis',
                    'data'        => array_map(fn($h) => $proyeksi['pesimis'][$h] ?? 0, $titikHari),
                    'borderColor' => '#dc2626',
                    'borderDash'  => [3, 3],
                    'fill'        => false,
                    'tension'     => 0.3,
                    'pointRadius' => 4,
                ],
            ],
            'labels' => $labelHari,
        ];
    }

    protected function getProyeksiDariGemini(
        int $pendapatanHarian,
        int $pengeluaranHarian,
        float $saldoAwal
    ): array {
        $apiKey = env('GEMINI_API_KEY');

        $prompt = "Kamu adalah analis keuangan bisnis laundry. 
Berdasarkan data berikut:
- Rata-rata pendapatan harian: Rp {$pendapatanHarian}
- Rata-rata pengeluaran harian: Rp {$pengeluaranHarian}
- Saldo awal proyeksi: Rp {$saldoAwal}

Buatkan proyeksi saldo kas untuk hari ke 1, 5, 10, 15, 20, 25, 30 dalam 3 skenario.
Berikan HANYA response dalam format JSON seperti ini, tanpa penjelasan apapun:
{
  \"optimis\": {\"1\": 1100000, \"5\": 1200000, \"10\": 1350000, \"15\": 1500000, \"20\": 1650000, \"25\": 1800000, \"30\": 2000000},
  \"realistis\": {\"1\": 1000000, \"5\": 1050000, \"10\": 1100000, \"15\": 1150000, \"20\": 1200000, \"25\": 1250000, \"30\": 1300000},
  \"pesimis\": {\"1\": 900000, \"5\": 850000, \"10\": 800000, \"15\": 750000, \"20\": 700000, \"25\": 650000, \"30\": 600000},
  \"ringkasan\": \"Analisis singkat 1 kalimat tentang kondisi keuangan laundry\"
}";

        try {
            $response = Http::timeout(30)->post(
                // "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key={$apiKey}",
                // "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}",
                "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ]
                ]
            );

            $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Bersihkan response dari markdown code block jika ada
            $text = preg_replace('/```json|```/', '', $text);
            $text = trim($text);

            $data = json_decode($text, true);

            if ($data && isset($data['optimis'])) {
                // Simpan ringkasan AI untuk ditampilkan di heading
                static::$aiSummary = '🤖 AI: ' . ($data['ringkasan'] ?? 'Analisis selesai');

                return [
                    'optimis'   => $data['optimis'],
                    'realistis' => $data['realistis'],
                    'pesimis'   => $data['pesimis'],
                ];
            }
        } catch (\Exception $e) {
            // Jika Gemini gagal, fallback ke formula
            static::$aiSummary = '⚠️ Menggunakan kalkulasi lokal (AI tidak tersedia)';
        }

        // Fallback formula jika Gemini gagal
        $titikHari = [1, 5, 10, 15, 20, 25, 30];
        $optimis = $realistis = $pesimis = [];

        foreach ($titikHari as $h) {
            $r = max($saldoAwal + (($pendapatanHarian - $pengeluaranHarian) * $h), 0);
            $optimis[$h]   = round($r * 1.15);
            $realistis[$h] = round($r);
            $pesimis[$h]   = round($r * 0.85);
        }

        return compact('optimis', 'realistis', 'pesimis');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => ['beginAtZero' => false],
            ],
        ];
    }
}