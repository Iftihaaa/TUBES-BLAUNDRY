<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Notifications\Notification;
use App\Models\ParfumTrend;
use Illuminate\Support\Facades\Http;

class TrendParfumChart extends ChartWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = '1';

    public ?string $filter = 'current';

    public function getHeading(): string
    {
        return '🤖 Tren Parfum Laundry ' . now()->year . ' — Powered by Gemini AI';
    }

    public function getDescription(): ?string
    {
        $latest = ParfumTrend::latest()->first();
        if ($latest) {
            return '🤖 AI: ' . $latest->rekomendasi;
        }
        return '⚠️ Belum ada data — gunakan tombol Refresh di halaman Tren Parfum AI';
    }

    protected function getFilters(): ?array
    {
        return [
            'current' => 'Data Saat Ini',
            'refresh' => '🔄 Refresh dari Gemini AI',
        ];
    }

    protected function getData(): array
    {
        // Jika filter refresh dipilih, panggil Gemini
        if ($this->filter === 'refresh') {
            $this->refreshDariGemini();
            $this->filter = 'current';
        }

        $latest = ParfumTrend::latest()->first();

        if (!$latest || !$latest->parfum_populer) {
            return [
                'datasets' => [[
                    'label'           => 'Popularitas Parfum',
                    'data'            => [0],
                    'backgroundColor' => ['rgba(99,102,241,0.8)'],
                ]],
                'labels' => ['Belum ada data'],
            ];
        }

        $parfum = $latest->parfum_populer;
        $labels = array_column($parfum, 'nama');
        $values = array_column($parfum, 'skor');

        $warna = [
            'rgba(99,102,241,0.8)',
            'rgba(16,185,129,0.8)',
            'rgba(245,158,11,0.8)',
            'rgba(239,68,68,0.8)',
            'rgba(139,92,246,0.8)',
            'rgba(14,165,233,0.8)',
            'rgba(236,72,153,0.8)',
            'rgba(234,179,8,0.8)',
            'rgba(20,184,166,0.8)',
            'rgba(249,115,22,0.8)',
        ];

        return [
            'datasets' => [[
                'label'           => 'Popularitas Parfum',
                'data'            => $values,
                'backgroundColor' => array_slice($warna, 0, count($labels)),
            ]],
            'labels' => $labels,
        ];
    }

    protected function refreshDariGemini(): void
    {
        $apiKey = env('GEMINI_API_KEY');

        $prompt = "Kamu adalah analis bisnis laundry profesional.
Analisis tren parfum/wewangian yang populer untuk bisnis laundry di Indonesia tahun " . now()->year . ".
Berikan HANYA response dalam format JSON seperti ini, tanpa penjelasan apapun:
{
  \"nama_tren\": \"Tren Parfum Laundry " . now()->year . "\",
  \"analisis_ai\": \"Penjelasan singkat tren parfum laundry tahun ini\",
  \"aroma_terpopuler\": \"nama aroma paling populer\",
  \"rekomendasi\": \"Rekomendasi singkat 1 kalimat untuk bisnis laundry\",
  \"parfum_populer\": [
    {\"nama\": \"Fresh Linen\", \"skor\": 9},
    {\"nama\": \"Soft Blossom\", \"skor\": 8},
    {\"nama\": \"Lavender\", \"skor\": 8},
    {\"nama\": \"Baby Powder\", \"skor\": 7},
    {\"nama\": \"Ocean Breeze\", \"skor\": 7},
    {\"nama\": \"Vanilla\", \"skor\": 6},
    {\"nama\": \"Green Tea\", \"skor\": 6},
    {\"nama\": \"Rose\", \"skor\": 5},
    {\"nama\": \"Jasmine\", \"skor\": 5},
    {\"nama\": \"Oudh\", \"skor\": 4}
  ]
}";

        try {
            $response = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                ['contents' => [['parts' => [['text' => $prompt]]]]]
            );

            $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $text = preg_replace('/```json|```/', '', $text);
            $text = trim($text);
            $data = json_decode($text, true);

            if ($data && isset($data['parfum_populer'])) {
                ParfumTrend::create([
                    'nama_tren'        => $data['nama_tren'],
                    'analisis_ai'      => $data['analisis_ai'],
                    'parfum_populer'   => $data['parfum_populer'],
                    'aroma_terpopuler' => $data['aroma_terpopuler'],
                    'rekomendasi'      => $data['rekomendasi'],
                ]);

                Notification::make()
                    ->title('✅ Analisis AI Selesai!')
                    ->body('Tren parfum berhasil dianalisis Gemini AI.')
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Gagal')
                ->body('Tidak dapat terhubung ke Gemini AI.')
                ->danger()
                ->send();
        }
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'max' => 10],
            ],
        ];
    }
}