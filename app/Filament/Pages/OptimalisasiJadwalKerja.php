<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Pegawai;
use App\Models\Pemesanan;

class OptimalisasiJadwalKerja extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static string $view = 'filament.pages.optimalisasi-jadwal-kerja';
    protected static ?string $navigationLabel = 'Optimalisasi Jadwal';
    protected static ?string $title = 'AI Optimalisasi Jadwal Kerja';
    protected static ?string $navigationGroup = 'Analisis AI';

    public string $errorPesan    = '';
    public string $peakDay       = '';
    public string $ringkasan     = '';
    public string $saranShift    = '';
    public array  $jadwal        = [];
    public array  $catatanPegawai = [];

    public function analisis(): void
    {
        // Reset state
        $this->errorPesan     = '';
        $this->peakDay        = '';
        $this->ringkasan      = '';
        $this->saranShift     = '';
        $this->jadwal         = [];
        $this->catatanPegawai = [];

        // ── 1. DATA PEGAWAI ───────────────────────────────────────────────────
        $pegawai = Pegawai::all(['id_pegawai', 'nama', 'jabatan', 'gaji_pokok']);

        // ── 2. ABSENSI HISTORIS per pegawai (30 hari terakhir) ────────────────
        $absensiRekap = collect(DB::select("
            SELECT pegawai_id,
                   COUNT(*) as total_hari,
                   SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                   SUM(CASE WHEN status = 'izin'  THEN 1 ELSE 0 END) as izin,
                   SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                   SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha
            FROM absensis
            WHERE tanggal >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY pegawai_id
        "))->keyBy('pegawai_id');

        // ── 3. DISTRIBUSI PESANAN per hari (4 minggu terakhir) ───────────────
        $pesananPerHari = DB::select("
            SELECT DAYNAME(created_at) as hari_en,
                   DAYOFWEEK(created_at) as urutan,
                   COUNT(*) as jumlah_pesanan
            FROM pemesanan
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 28 DAY)
            GROUP BY DAYNAME(created_at), DAYOFWEEK(created_at)
            ORDER BY DAYOFWEEK(created_at)
        ");

        $hariIndo = [
            'Sunday'    => 'Minggu', 'Monday'   => 'Senin',  'Tuesday'  => 'Selasa',
            'Wednesday' => 'Rabu',   'Thursday' => 'Kamis',  'Friday'   => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];

        // ── 4. PESANAN AKTIF & 7 HARI TERAKHIR ───────────────────────────────
        $pesananAktif  = Pemesanan::where('status', 'on process')->count();
        $pemesanan7Hari = Pemesanan::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as jumlah')
            ->groupBy('tanggal')->orderBy('tanggal')->get();

        // ── SUSUN PROMPT ──────────────────────────────────────────────────────
        $promptPegawai = $pegawai->map(function ($p) use ($absensiRekap) {
            $absen   = $absensiRekap->get($p->id_pegawai);
            $gajiStr = $p->gaji_pokok ? 'Rp ' . number_format($p->gaji_pokok, 0, ',', '.') : '-';
            if ($absen && $absen->total_hari > 0) {
                $persen = round(($absen->hadir / $absen->total_hari) * 100) . '%';
                return "- {$p->nama} ({$p->jabatan}, gaji: {$gajiStr})"
                     . " | hadir={$absen->hadir}, izin={$absen->izin}, sakit={$absen->sakit}, alpha={$absen->alpha} ({$persen})";
            }
            return "- {$p->nama} ({$p->jabatan}, gaji: {$gajiStr}) | Belum ada data absensi";
        })->join("\n");

        $promptDistribusi = count($pesananPerHari) > 0
            ? collect($pesananPerHari)->map(fn($d) => "  " . ($hariIndo[$d->hari_en] ?? $d->hari_en) . ": {$d->jumlah_pesanan} pesanan")->join("\n")
            : '  Belum ada data historis';

        $promptPemesanan = $pemesanan7Hari->isNotEmpty()
            ? $pemesanan7Hari->map(fn($p) => "  {$p->tanggal}: {$p->jumlah} pesanan")->join("\n")
            : '  Belum ada data';

        $prompt = <<<PROMPT
Kamu adalah asisten manajer operasional laundry. Analisis data berikut dan buat jadwal kerja pegawai optimal untuk MINGGU DEPAN.

=== DATA PEGAWAI & ABSENSI (30 HARI TERAKHIR) ===
{$promptPegawai}

=== VOLUME PESANAN PER HARI (4 MINGGU TERAKHIR) ===
{$promptDistribusi}

=== PESANAN 7 HARI TERAKHIR ===
{$promptPemesanan}

=== STATUS AKTIF ===
- Pesanan on process saat ini: {$pesananAktif} pesanan

=== FORMAT OUTPUT ===
Kembalikan HANYA JSON valid berikut (tanpa markdown, tanpa teks di luar JSON):
{
  "peak_day": "nama hari tersibuk",
  "ringkasan": "ringkasan 1-2 kalimat kondisi operasional minggu depan",
  "saran_shift": "saran konkret terkait penambahan atau pengurangan shift",
  "jadwal": [
    {
      "hari": "Senin",
      "pegawai": [
        { "nama": "...", "jabatan": "...", "jam_masuk": "HH:MM", "jam_keluar": "HH:MM" }
      ],
      "volume": "rendah",
      "catatan": "catatan singkat hari ini"
    }
  ],
  "catatan_pegawai": [
    { "nama": "...", "catatan": "..." }
  ]
}
Isi jadwal untuk 7 hari (Senin sampai Minggu). Nilai volume hanya boleh: rendah, sedang, atau tinggi.
PROMPT;

        $apiKey = env('GROQ_API_KEY');
        if (empty($apiKey)) {
            $this->errorPesan = '❌ GROQ_API_KEY belum dikonfigurasi di file .env';
            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'Kamu adalah asisten manajemen laundry. Selalu kembalikan HANYA JSON valid tanpa markdown code block, tanpa penjelasan tambahan.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.3,
            'max_tokens'  => 2048,
        ]);

        if (! $response->successful()) {
            $this->errorPesan = '❌ Gagal menghubungi AI: ' . ($response->json('error.message') ?? $response->body());
            return;
        }

        $raw = $response->json('choices.0.message.content') ?? '';

        // Bersihkan jika AI masih menyertakan markdown fence
        $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $raw = preg_replace('/\s*```$/', '', $raw);

        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! isset($data['jadwal'])) {
            $this->errorPesan = '⚠️ AI mengembalikan format yang tidak valid. Coba generate ulang.';
            return;
        }

        $this->peakDay        = $data['peak_day']       ?? '-';
        $this->ringkasan      = $data['ringkasan']      ?? '';
        $this->saranShift     = $data['saran_shift']    ?? '';
        $this->jadwal         = $data['jadwal']         ?? [];
        $this->catatanPegawai = $data['catatan_pegawai'] ?? [];
    }
}