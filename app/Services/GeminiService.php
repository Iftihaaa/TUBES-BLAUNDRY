<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected ?string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
        $this->model = config('services.gemini.model', 'gemini-2.5-flash');
    }

    public function generateText(string $prompt): string
    {
        $response = Http::timeout(60)->post(
            "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
            ]
        );

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    public function analisaLabaRugi(array $data): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException(
                'GEMINI_API_KEY belum dikonfigurasi di file .env.'
            );  
        }
        
        $prompt = $this->buildPrompt($data);

        $response = Http::timeout(90)->post(
            "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json',
                ],
            ]
        );

        if ($response->failed()) {
            Log::error('Gemini API error', ['body' => $response->body()]);
            throw new \RuntimeException(
                'Gemini error (' . $response->status() . '): '
                . $response->json('error.message', $response->body())
            );
        }

        $rawText = $response->json('candidates.0.content.parts.0.text', '');

        $clean = trim($rawText);
        $clean = preg_replace('/^```(json)?/i', '', $clean);
        $clean = preg_replace('/```$/', '', $clean);
        $clean = trim($clean);

        $parsed = json_decode($clean, true);

        if (! is_array($parsed)) {
            $parsed = [
                'status_keuangan' => 'Tidak diketahui',
                'ringkasan' => $rawText ?: 'AI tidak memberikan jawaban yang dapat dibaca.',
                'analisis_pendapatan' => '',
                'analisis_beban' => '',
                'analisis_margin' => '',
                'rekomendasi' => [],
                'kesimpulan' => '',
            ];
        }

        return [
            'status_keuangan' => $parsed['status_keuangan'] ?? 'Tidak diketahui',
            'ringkasan' => $parsed['ringkasan'] ?? '',
            'analisis_pendapatan' => $parsed['analisis_pendapatan'] ?? '',
            'analisis_beban' => $parsed['analisis_beban'] ?? '',
            'analisis_margin' => $parsed['analisis_margin'] ?? '',
            'rekomendasi' => array_values((array) ($parsed['rekomendasi'] ?? [])),
            'kesimpulan' => $parsed['kesimpulan'] ?? '',
            'raw_response' => $rawText,
        ];
    }

    /**
     * Menjawab pertanyaan bebas dari pengguna (chat asisten) dengan
     * konteks data laba rugi periode terpilih.
     */
    public function jawabPertanyaan(string $pertanyaan, array $data, array $riwayat = []): string
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY belum dikonfigurasi di file .env.');
        }

        $namaBulan = $data['nama_bulan'] ?? '';
        $tahun = $data['tahun'] ?? '';
        $pendapatan = number_format($data['total_pendapatan'] ?? 0, 0, ',', '.');
        $modal = number_format($data['total_modal'] ?? 0, 0, ',', '.');
        $beban = number_format($data['total_beban'] ?? 0, 0, ',', '.');
        $laba = number_format($data['laba_bersih'] ?? 0, 0, ',', '.');

        $konteks = "Data Laporan Laba Rugi periode {$namaBulan} {$tahun}:\n"
            . "- Total Pendapatan: Rp {$pendapatan}\n"
            . "- Total Modal: Rp {$modal}\n"
            . "- Total Beban: Rp {$beban}\n"
            . "- Laba Bersih: Rp {$laba}";

        $riwayatText = '';
        foreach ($riwayat as $r) {
            $who = ($r['role'] ?? '') === 'user' ? 'Pengguna' : 'Asisten';
            $riwayatText .= "{$who}: " . ($r['text'] ?? '') . "\n";
        }
        if ($riwayatText === '') {
            $riwayatText = '(belum ada)';
        }

        $prompt = <<<PROMPT
Kamu adalah asisten keuangan ramah untuk pemilik usaha laundry (BLaundry) yang tidak mengerti istilah akuntansi.
Jawab pertanyaan dengan Bahasa Indonesia yang sederhana, singkat, jelas, dan langsung ke inti.
Gunakan data laporan laba rugi di bawah ini sebagai acuan utama. Jika pertanyaan di luar data, jawab secara umum tapi tetap relevan dengan usaha laundry.

{$konteks}

Riwayat percakapan sebelumnya:
{$riwayatText}

Pertanyaan baru pengguna: {$pertanyaan}
PROMPT;

        $response = Http::timeout(60)->post(
            "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                ],
            ]
        );

        if ($response->failed()) {
            Log::error('Gemini chat error', ['body' => $response->body()]);
            throw new \RuntimeException(
                'Gemini error (' . $response->status() . '): '
                . $response->json('error.message', $response->body())
            );
        }

        return $response->json('candidates.0.content.parts.0.text', 'Maaf, saya tidak punya jawaban untuk itu.');
    }

    protected function parseAnalysisResponse(string $rawText): array
    {
        $clean = trim($rawText);
        $clean = preg_replace('/^```(json)?/i', '', $clean);
        $clean = preg_replace('/```$/', '', $clean);
        $clean = trim($clean);

        $parsed = json_decode($clean, true);

        if (! is_array($parsed)) {
            $parsed = [
                'status_keuangan' => 'Tidak diketahui',
                'ringkasan' => $rawText ?: 'AI tidak memberikan jawaban yang dapat dibaca.',
                'analisis_pendapatan' => '',
                'analisis_beban' => '',
                'analisis_margin' => '',
                'rekomendasi' => [],
                'kesimpulan' => '',
            ];
        }

        return [
            'status_keuangan' => $parsed['status_keuangan'] ?? 'Tidak diketahui',
            'ringkasan' => $parsed['ringkasan'] ?? '',
            'analisis_pendapatan' => $parsed['analisis_pendapatan'] ?? '',
            'analisis_beban' => $parsed['analisis_beban'] ?? '',
            'analisis_margin' => $parsed['analisis_margin'] ?? '',
            'rekomendasi' => array_values((array) ($parsed['rekomendasi'] ?? [])),
            'kesimpulan' => $parsed['kesimpulan'] ?? '',
            'raw_response' => $rawText,
        ];
    }

        protected function buildPrompt(array $data): string
    {
        $namaBulan = $data['nama_bulan'] ?? $data['bulan'];
        $tahun = $data['tahun'];
        $pendapatan = number_format($data['total_pendapatan'] ?? 0, 0, ',', '.');
        $modal = number_format($data['total_modal'] ?? 0, 0, ',', '.');
        $beban = number_format($data['total_beban'] ?? 0, 0, ',', '.');
        $laba = number_format($data['laba_bersih'] ?? 0, 0, ',', '.');

        $rincianPendapatan = $this->formatRincian($data['rincian_pendapatan'] ?? []);
        $rincianBeban = $this->formatRincian($data['rincian_beban'] ?? []);

        $konteksTambahan = trim($data['konteks_tambahan'] ?? '');
        $blokKonteks = $konteksTambahan !== ''
            ? "\n\nData tren & proyeksi (WAJIB kamu bahas di ringkasan, rekomendasi, dan kesimpulan):\n{$konteksTambahan}"
            : '';

        return <<<PROMPT
Kamu adalah konsultan keuangan ramah untuk pemilik usaha laundry (BLaundry) yang TIDAK mengerti istilah akuntansi.
Tugasmu menerjemahkan laporan laba rugi menjadi penjelasan yang mudah dipahami orang awam, dalam Bahasa Indonesia yang santai, jelas, dan memotivasi.

Berikut data Laporan Laba Rugi untuk periode {$namaBulan} {$tahun}:
- Total Pendapatan: Rp {$pendapatan}
- Total Modal: Rp {$modal}
- Total Biaya/Beban: Rp {$beban}
- Laba Bersih: Rp {$laba}

Rincian sumber pendapatan:
{$rincianPendapatan}

Rincian beban/biaya:
{$rincianBeban}{$blokKonteks}

Analisa data di atas. Jangan gunakan istilah akuntansi yang rumit; jika harus, jelaskan dengan analogi sederhana.
Bila ada data tren, sebutkan apakah laba sedang NAIK atau TURUN dibanding bulan-bulan sebelumnya dan kira-kira kenapa.
Bila ada data proyeksi, jelaskan dengan bahasa membumi berapa pesanan yang perlu dikejar bulan depan dan beri strategi nyata untuk mencapainya.
Hitung juga margin laba (laba bersih dibanding pendapatan) dan jelaskan artinya.

Balas HANYA dalam format JSON valid (tanpa teks lain) dengan struktur persis seperti ini:
{
  "status_keuangan": "salah satu dari: Sehat, Perlu Perhatian, atau Kritis",
  "ringkasan": "2-3 kalimat ringkas bahasa awam: bulan ini untung/rugi, dan tren-nya naik atau turun",
  "analisis_pendapatan": "penjelasan singkat soal pemasukan periode ini",
  "analisis_beban": "penjelasan singkat soal pengeluaran terbesar dan apakah wajar",
  "analisis_margin": "penjelasan margin laba dalam bahasa awam, sebutkan persentasenya",
  "rekomendasi": ["saran konkret 1", "saran konkret 2", "saran terkait target pesanan bulan depan"],
  "kesimpulan": "1-2 kalimat kesimpulan memotivasi yang menyinggung target bulan depan"
}
PROMPT;
    }

    protected function formatRincian(array $rincian): string
    {
        if (empty($rincian)) {
            return '- (tidak ada rincian)';
        }

        $lines = [];
        foreach ($rincian as $item) {
            $nama = $item['nama_akun'] ?? '-';
            $jumlah = number_format($item['jumlah'] ?? 0, 0, ',', '.');
            $lines[] = "- {$nama}: Rp {$jumlah}";
        }

        return implode("\n", $lines);
    }
}