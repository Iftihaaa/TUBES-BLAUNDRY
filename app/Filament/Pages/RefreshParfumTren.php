<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use App\Models\ParfumTrend;

class RefreshParfumTren extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Tren Parfum AI';
    protected static ?string $navigationGroup = 'Analisis AI';
    protected static ?string $title = 'Tren Parfum Laundry — AI Insight';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.refresh-parfum-tren';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshTren')
                ->label('🤖 Refresh Tren Parfum')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Analisis Tren Parfum')
                ->modalDescription('Sistem akan menghubungi Gemini AI untuk menganalisis tren parfum laundry terbaru. Lanjutkan?')
                ->action(function () {
                    $apiKey = env('GEMINI_API_KEY');

                        $prompt = "Kamu adalah analis bisnis laundry profesional di Indonesia.
Analisis dan tentukan sendiri tren parfum/wewangian yang paling populer untuk bisnis laundry di Indonesia tahun " . now()->year . ".
JANGAN gunakan data contoh, gunakan pengetahuanmu sendiri.
Berikan response dalam format JSON berikut, tanpa penjelasan apapun, tanpa markdown:
{
  \"nama_tren\": \"Tren Parfum Laundry Indonesia " . now()->year . "\",
  \"analisis_ai\": \"penjelasan singkat tren parfum laundry tahun ini berdasarkan analisismu\",
  \"aroma_terpopuler\": \"nama satu aroma paling populer menurutmu\",
  \"rekomendasi\": \"rekomendasi singkat 1 kalimat untuk bisnis laundry\",
  \"parfum_populer\": [
    {\"nama\": \"nama parfum 1\", \"skor\": 9},
    {\"nama\": \"nama parfum 2\", \"skor\": 8},
    {\"nama\": \"nama parfum 3\", \"skor\": 8},
    {\"nama\": \"nama parfum 4\", \"skor\": 7},
    {\"nama\": \"nama parfum 5\", \"skor\": 7},
    {\"nama\": \"nama parfum 6\", \"skor\": 6},
    {\"nama\": \"nama parfum 7\", \"skor\": 6},
    {\"nama\": \"nama parfum 8\", \"skor\": 5},
    {\"nama\": \"nama parfum 9\", \"skor\": 5},
    {\"nama\": \"nama parfum 10\", \"skor\": 4}
  ]
}";

//                     $prompt = "Kamu adalah analis bisnis laundry profesional.
// Analisis tren parfum/wewangian yang populer untuk bisnis laundry di Indonesia tahun " . now()->year . ".
// Berikan HANYA response dalam format JSON seperti ini, tanpa penjelasan apapun:
// {
//   \"nama_tren\": \"Tren Parfum Laundry " . now()->year . "\",
//   \"analisis_ai\": \"Penjelasan singkat tren parfum laundry tahun ini\",
//   \"aroma_terpopuler\": \"nama aroma paling populer\",
//   \"rekomendasi\": \"Rekomendasi singkat 1 kalimat untuk bisnis laundry\",
//   \"parfum_populer\": [
//     {\"nama\": \"Floral Fresh\", \"skor\": 9},
//     {\"nama\": \"Lemon Citrus\", \"skor\": 8},
//     {\"nama\": \"Lavender\", \"skor\": 8},
//     {\"nama\": \"Baby Soft\", \"skor\": 7},
//     {\"nama\": \"Ocean Breeze\", \"skor\": 7},
//     {\"nama\": \"Vanilla\", \"skor\": 6},
//     {\"nama\": \"Green Tea\", \"skor\": 6},
//     {\"nama\": \"Rose\", \"skor\": 5},
//     {\"nama\": \"Jasmine\", \"skor\": 5},
//     {\"nama\": \"Oudh\", \"skor\": 4}
//   ]
// }";

                    try {
                        $response = Http::timeout(60)->post(
                            "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                            [
                                'contents' => [
                                    ['parts' => [['text' => $prompt]]]
                                ]
                            ]
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
                                ->body('Tren parfum laundry berhasil dianalisis oleh Gemini AI.')
                                ->success()
                                ->send();

                            $this->dispatch('$refresh'); 
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('❌ Gagal')
                            ->body('Tidak dapat terhubung ke Gemini AI: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getViewData(): array
    {
        $latest = ParfumTrend::latest()->first();
        return ['latest' => $latest];
    }
}