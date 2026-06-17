<?php

namespace App\Filament\Resources\AnalisisCashflowResource\Pages;

use App\Filament\Resources\AnalisisCashflowResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use App\Models\AnalisisCashflow;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class ManageAnalisisCashflows extends ManageRecords
{
    protected static string $resource = AnalisisCashflowResource::class;

    protected function getHeaderActions(): array
    {
        $periode = now()->format('Y-m');

        return [
            Actions\Action::make('refreshAiCashflow')
                ->label('Analisis AI Cashflow')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading("Analisis Cashflow $periode")
                ->modalDescription("Sistem akan mengambil data cashflow bulan ini dan mengirimkannya ke AI. Lanjutkan?")
                ->action(function () {

                    try {
                        // ── 1. AMBIL DATA CASHFLOW ──────────────
                        $bulan = now()->month;
                        $tahun = now()->year;

                        $totalPemasukan = DB::table('jurnal_detail')
                            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
                            ->where('jurnal_detail.coa_id', 1)
                            ->where('jurnal_detail.debit', '>', 0)
                            ->whereMonth('jurnal.tgl', $bulan)
                            ->whereYear('jurnal.tgl', $tahun)
                            ->sum('jurnal_detail.debit');

                        $totalPengeluaran = DB::table('jurnal_detail')
                            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
                            ->where('jurnal_detail.coa_id', 1)
                            ->where('jurnal_detail.credit', '>', 0)
                            ->whereMonth('jurnal.tgl', $bulan)
                            ->whereYear('jurnal.tgl', $tahun)
                            ->sum('jurnal_detail.credit');

                        $breakdownPengeluaran = DB::table('jurnal_detail')
                            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
                            ->join('akuncoa', 'akuncoa.id', '=', 'jurnal_detail.coa_id')
                            ->where('jurnal_detail.coa_id', '!=', 1)
                            ->where('jurnal_detail.debit', '>', 0)
                            ->whereMonth('jurnal.tgl', $bulan)
                            ->whereYear('jurnal.tgl', $tahun)
                            ->select('akuncoa.nama_akun', DB::raw('SUM(jurnal_detail.debit) as total'))
                            ->groupBy('akuncoa.nama_akun')
                            ->get()
                            ->map(fn($item) => "{$item->nama_akun}: Rp " . number_format($item->total, 0, ',', '.'))
                            ->implode(', ');

                        $netCashflow = $totalPemasukan - $totalPengeluaran;
                        $status      = $netCashflow >= 0 ? 'SURPLUS' : 'DEFISIT';

                        // ── 2. PROMPT ────────────────────────────
                        $periodeFormatted = now()->format('F Y');
                        $prompt = "Berikut data cashflow usaha laundry bulan {$periodeFormatted}:
- Total Pemasukan: Rp " . number_format($totalPemasukan, 0, ',', '.') . "
- Total Pengeluaran: Rp " . number_format($totalPengeluaran, 0, ',', '.') . "
- Net Cashflow: Rp " . number_format(abs($netCashflow), 0, ',', '.') . " ({$status})
- Rincian Pengeluaran: {$breakdownPengeluaran}

Berikan analisis keuangan usaha laundry ini.
WAJIB balas dalam format JSON murni seperti contoh di bawah ini (TANPA teks tambahan apapun, TANPA markdown, TANPA backtick):
{
  \"analisis_ai\": \"penjelasan kondisi cashflow\",
  \"kesimpulan\": \"kesimpulan singkat\",
  \"saran_operasional\": \"saran konkret\"
}";

                        // ── 3. CEK API KEY ─────────────────────
                        $apiKey = env('GEMINI_API_KEY');
                        if (empty($apiKey)) {
                            throw new \Exception("Kunci GEMINI_API_KEY di file .env masih kosong.");
                        }

                        // ── 4. KIRIM KE GEMINI API (dengan fallback) ────
                        $models = [
                            'gemini-2.5-flash-preview-05-20',
                            'gemini-2.5-flash',
                            'gemini-1.5-flash',
                            'gemini-1.5-flash-8b',
                        ];

                        $response    = null;
                        $usedModel   = null;

                        foreach ($models as $model) {
                            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

                            $response = Http::timeout(60)
                                ->withHeaders(['Content-Type' => 'application/json'])
                                ->post($endpoint, [
                                    'contents' => [
                                        [
                                            'parts' => [
                                                ['text' => $prompt]
                                            ]
                                        ]
                                    ],
                                    'generationConfig' => [
                                        'temperature' => 0.3,
                                    ],
                                ]);

                            if ($response->successful()) {
                                $usedModel = $model;
                                break;
                            }
                        }

                        if (!$response || !$response->successful()) {
                            throw new \Exception(
                                "Semua model Gemini gagal. Error terakhir: [" .
                                $response->status() . "] " .
                                substr($response->body(), 0, 300)
                            );
                        }

                        // ── 5. AMBIL TEKS RESPONS ─────────────
                        $rawText = $response->json('candidates.0.content.parts.0.text');

                        if ($rawText === null || trim($rawText) === '') {
                            throw new \Exception(
                                "Teks kosong dari model {$usedModel}! Balasan asli: " .
                                substr($response->body(), 0, 300)
                            );
                        }

                        // ── 6. EKSTRAK JSON ───────────────────
                        $cleaned = preg_replace('/```json|```/', '', $rawText);
                        preg_match('/\{[\s\S]*\}/', $cleaned, $matches);
                        $jsonString = $matches[0] ?? '';

                        $data = json_decode($jsonString, true);

                        if (!$data) {
                            throw new \Exception(
                                "Gagal parsing JSON dari model {$usedModel}. Balasan Asli: " .
                                substr(strip_tags($rawText), 0, 150)
                            );
                        }

                        // ── 7. SIMPAN KE DATABASE ─────────────
                        AnalisisCashflow::create([
                            'periode'           => now()->startOfMonth()->toDateString(),
                            'analisis_ai'       => $data['analisis_ai']      ?? 'Tidak ada data',
                            'kesimpulan'        => $data['kesimpulan']        ?? 'Tidak ada data',
                            'saran_operasional' => $data['saran_operasional'] ?? 'Tidak ada data',
                        ]);

                        // ── Notifikasi Sukses ─────────────────
                        Notification::make()
                            ->title('Analisis Cashflow Berhasil')
                            ->body("Net cashflow bulan ini: Rp " . number_format(abs($netCashflow), 0, ',', '.') . " ($status)")
                            ->success()
                            ->send();

                        // ── Auto redirect (paksa halaman reload) ─────────────
                        $this->redirect(request()->header('Referer'), navigate: true);

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Oops! Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    // ── DAFTARKAN WIDGET ──────────────────────────────────────────
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\AnalisisCashflowResource\Widgets\CashflowBarChart::class,
            \App\Filament\Resources\AnalisisCashflowResource\Widgets\CashflowDoughnutChart::class,
            \App\Filament\Resources\AnalisisCashflowResource\Widgets\LatestCashflowInsight::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2; // bar chart full, pie & insight sebelahan
    }
}