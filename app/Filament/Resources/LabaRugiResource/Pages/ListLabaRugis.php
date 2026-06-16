<?php

namespace App\Filament\Resources\LabaRugiResource\Pages;

use App\Filament\Resources\LabaRugiResource;
use App\Models\AnalisaLabaRugi;
use App\Services\GeminiService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ListLabaRugis extends Page
{
    protected static string $resource = LabaRugiResource::class;

    protected static string $view =
        'filament.resources.laba-rugi-resource.pages.list-laba-rugis';

    protected ?string $heading = 'Laporan Laba Rugi';

    // Filter periode
    public int $bulan;
    public int $tahun;

    // Angka laba rugi
    public $totalPendapatan = 0;
    public $totalModal = 0;
    public $totalBeban = 0;
    public $labaBersih = 0;
    public $marginLaba = 0;

    // Rincian per akun
    public array $rincianPendapatan = [];
    public array $rincianBeban = [];

    // Data pesanan periode ini
    public int $jumlahPesanan = 0;
    public float $rataRataPesanan = 0; // rata-rata nilai per pesanan (AOV)

    // Tren & proyeksi
    public array $trenLaba = [];
    public array $proyeksi = [];
    public float $targetKenaikan = 10; // persen, bisa digeser user

    // Analisa AI tersimpan
    public ?array $analisa = null;
    public ?string $analisaDibuatPada = null;

    // Chat asisten AI
    public array $chat = [];
    public string $pertanyaan = '';

    public function mount(): void
    {
        $this->bulan = (int) now()->month;
        $this->tahun = (int) now()->year;
        $this->loadData();
        $this->loadAnalisa();
    }

    public function updatedBulan(): void
    {
        $this->refreshPeriode();
    }

    public function updatedTahun(): void
    {
        $this->refreshPeriode();
    }

    /**
     * Saat slider target digeser -> hitung ulang proyeksi saja (ringan).
     */
    public function updatedTargetKenaikan(): void
    {
        $this->hitungProyeksi();
    }

    public function refreshPeriode(): void
    {
        $this->loadData();
        $this->loadAnalisa();
    }

    /**
     * Hitung angka laba (pendapatan/modal/beban/laba) untuk SATU periode.
     */
    private function angkaLaba(int $bulan, int $tahun): array
    {
        $base = DB::table('jurnal_detail')
            ->join('akuncoa', 'akuncoa.id', '=', 'jurnal_detail.coa_id')
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
            ->whereYear('jurnal.tgl', $tahun)
            ->whereMonth('jurnal.tgl', $bulan);

        $pendapatan = (float) (clone $base)->where('akuncoa.header_akun', 4)->sum('jurnal_detail.credit');
        $modal = (float) (clone $base)->where('akuncoa.header_akun', 3)->sum('jurnal_detail.credit');
        $beban = (float) (clone $base)->where('akuncoa.header_akun', 5)->sum('jurnal_detail.debit');

        return [
            'pendapatan' => $pendapatan,
            'modal' => $modal,
            'beban' => $beban,
            'laba' => $pendapatan + $modal - $beban,
        ];
    }

    /**
     * Data pesanan (jumlah & rata-rata nilai) untuk SATU periode.
     */
    private function dataPesanan(int $bulan, int $tahun): array
    {
        $q = DB::table('pemesanan')
            ->whereYear('tgl_pesan', $tahun)
            ->whereMonth('tgl_pesan', $bulan);

        $jumlah = (int) (clone $q)->count();
        $total = (float) (clone $q)->sum('total_harga');

        return [
            'jumlah' => $jumlah,
            'total' => $total,
            'aov' => $jumlah > 0 ? $total / $jumlah : 0,
        ];
    }

    /**
     * Muat semua data periode terpilih.
     */
    private function loadData(): void
    {
        $a = $this->angkaLaba($this->bulan, $this->tahun);
        $this->totalPendapatan = $a['pendapatan'];
        $this->totalModal = $a['modal'];
        $this->totalBeban = $a['beban'];
        $this->labaBersih = $a['laba'];

        $this->marginLaba = $this->totalPendapatan > 0
            ? round(($this->labaBersih / $this->totalPendapatan) * 100, 1)
            : 0;

        $this->rincianPendapatan = $this->rincian(4, 'credit');
        $this->rincianBeban = $this->rincian(5, 'debit');

        $p = $this->dataPesanan($this->bulan, $this->tahun);
        $this->jumlahPesanan = $p['jumlah'];
        $this->rataRataPesanan = $p['aov'];

        $this->loadTren();
        $this->hitungProyeksi();
    }

    /**
     * Tren laba bersih 6 bulan terakhir + persen naik/turun.
     */
    private function loadTren(): void
    {
        $tren = [];
        $awal = Carbon::create($this->tahun, $this->bulan, 1);
        $labaSebelumnya = null;

        for ($i = 2; $i >= 0; $i--) {
            $d = $awal->copy()->subMonths($i);
            $laba = $this->angkaLaba($d->month, $d->year)['laba'];

            $perubahan = null;
            if ($labaSebelumnya !== null && $labaSebelumnya != 0) {
                $perubahan = round((($laba - $labaSebelumnya) / abs($labaSebelumnya)) * 100, 1);
            }

            $tren[] = [
                'label' => $d->translatedFormat('M Y'),
                'laba' => $laba,
                'perubahan' => $perubahan,
            ];

            $labaSebelumnya = $laba;
        }

        $this->trenLaba = $tren;
    }

    /**
     * Proyeksi target pesanan bulan depan berdasarkan target kenaikan laba.
     * Beban diasumsikan = rata-rata 3 bulan terakhir (lebih stabil).
     */
    public function hitungProyeksi(): void
    {
        $awal = Carbon::create($this->tahun, $this->bulan, 1);

        // Rata-rata beban 3 bulan terakhir (yang ada datanya)
        $bebanList = [];
        for ($i = 0; $i < 3; $i++) {
            $d = $awal->copy()->subMonths($i);
            $b = $this->angkaLaba($d->month, $d->year)['beban'];
            if ($b > 0) {
                $bebanList[] = $b;
            }
        }
        $bebanAsumsi = count($bebanList) ? array_sum($bebanList) / count($bebanList) : $this->totalBeban;

        $target = max(0, (float) $this->targetKenaikan);
        $targetLaba = $this->labaBersih * (1 + $target / 100);

        // Asumsi tidak ada suntikan modal baru
        $targetPendapatan = $targetLaba + $bebanAsumsi;

        $aov = $this->rataRataPesanan;
        $targetPesanan = $aov > 0 ? (int) ceil($targetPendapatan / $aov) : 0;

        $bulanDepan = $awal->copy()->addMonth();
        $hari = $bulanDepan->daysInMonth;
        $perHari = $targetPesanan > 0 ? (int) ceil($targetPesanan / $hari) : 0;

        $this->proyeksi = [
            'target_persen' => $target,
            'beban_asumsi' => $bebanAsumsi,
            'target_laba' => $targetLaba,
            'target_pendapatan' => $targetPendapatan,
            'aov' => $aov,
            'target_pesanan' => $targetPesanan,
            'tambahan_pesanan' => max(0, $targetPesanan - $this->jumlahPesanan),
            'per_hari' => $perHari,
            'bulan_depan' => $bulanDepan->translatedFormat('F Y'),
            'bisa_proyeksi' => $aov > 0 && $this->labaBersih != 0,
        ];
    }

    /**
     * Rincian per akun.
     */
    private function rincian(int $headerAkun, string $kolom): array
    {
        return DB::table('jurnal_detail')
            ->join('akuncoa', 'akuncoa.id', '=', 'jurnal_detail.coa_id')
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
            ->whereYear('jurnal.tgl', $this->tahun)
            ->whereMonth('jurnal.tgl', $this->bulan)
            ->where('akuncoa.header_akun', $headerAkun)
            ->groupBy('akuncoa.nama_akun')
            ->selectRaw('akuncoa.nama_akun as nama_akun, SUM(jurnal_detail.' . $kolom . ') as jumlah')
            ->get()
            ->map(fn ($r) => [
                'nama_akun' => $r->nama_akun,
                'jumlah' => (float) $r->jumlah,
            ])
            ->toArray();
    }

    private function loadAnalisa(): void
    {
        $row = AnalisaLabaRugi::where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->first();

        if (! $row) {
            $this->analisa = null;
            $this->analisaDibuatPada = null;

            return;
        }

        $this->analisa = [
            'status_keuangan' => $row->status_keuangan,
            'ringkasan' => $row->ringkasan,
            'analisis_pendapatan' => $row->analisis_pendapatan,
            'analisis_beban' => $row->analisis_beban,
            'analisis_margin' => $row->analisis_margin,
            'rekomendasi' => $row->rekomendasi ?? [],
            'kesimpulan' => $row->kesimpulan,
        ];

        $this->analisaDibuatPada = optional($row->updated_at)->translatedFormat('d F Y H:i');
    }

    public function getNamaBulanProperty(): string
    {
        return Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F');
    }

    /**
     * Ringkasan tren & proyeksi untuk dikirim sebagai konteks ke AI.
     */
    private function ringkasanKonteksAi(): string
    {
        $baris = [];
        $baris[] = 'Tren laba bersih 6 bulan terakhir:';
        foreach ($this->trenLaba as $t) {
            $p = is_null($t['perubahan'])
                ? ''
                : ' (' . ($t['perubahan'] >= 0 ? '+' : '') . $t['perubahan'] . '% vs bulan sebelumnya)';
            $baris[] = '- ' . $t['label'] . ': Rp ' . number_format($t['laba'], 0, ',', '.') . $p;
        }

        if (! empty($this->proyeksi['bisa_proyeksi'])) {
            $pr = $this->proyeksi;
            $baris[] = '';
            $baris[] = 'Proyeksi untuk ' . $pr['bulan_depan'] . ' (target laba naik ' . $pr['target_persen'] . '%):';
            $baris[] = '- Rata-rata nilai per pesanan: Rp ' . number_format($pr['aov'], 0, ',', '.');
            $baris[] = '- Target pesanan: ' . $pr['target_pesanan'] . ' pesanan (sekitar ' . $pr['per_hari'] . ' per hari)';
            $baris[] = '- Jumlah pesanan bulan ini: ' . $this->jumlahPesanan;
        }

        return implode("\n", $baris);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analisaAi')
                ->label('Analisa dengan AI')
                ->icon('heroicon-m-sparkles')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Analisa Laba Rugi dengan AI')
                ->modalDescription(fn () => 'Sistem akan menghubungi Gemini AI untuk menganalisa laporan & tren laba rugi periode '
                    . $this->nama_bulan . ' ' . $this->tahun
                    . ' ke dalam bahasa yang mudah dipahami. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, analisa sekarang')
                ->action(function () {
                    $this->jalankanAnalisa();
                }),

            Actions\Action::make('hapusAnalisa')
                ->label('Hapus Analisa')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->outlined()
                ->visible(fn () => $this->analisa !== null)
                ->requiresConfirmation()
                ->action(function () {
                    AnalisaLabaRugi::where('bulan', $this->bulan)
                        ->where('tahun', $this->tahun)
                        ->delete();

                    $this->loadAnalisa();

                    Notification::make()
                        ->title('Analisa dihapus')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function jalankanAnalisa(): void
    {
        if ($this->totalPendapatan == 0 && $this->totalBeban == 0 && $this->totalModal == 0) {
            Notification::make()
                ->title('Belum ada data')
                ->body('Tidak ada transaksi pada periode ini untuk dianalisa.')
                ->warning()
                ->send();

            return;
        }

        try {
            $hasil = app(GeminiService::class)->analisaLabaRugi([
                'bulan' => $this->bulan,
                'tahun' => $this->tahun,
                'nama_bulan' => $this->nama_bulan,
                'total_pendapatan' => $this->totalPendapatan,
                'total_modal' => $this->totalModal,
                'total_beban' => $this->totalBeban,
                'laba_bersih' => $this->labaBersih,
                'rincian_pendapatan' => $this->rincianPendapatan,
                'rincian_beban' => $this->rincianBeban,
                'konteks_tambahan' => $this->ringkasanKonteksAi(),
            ]);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal menganalisa')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        AnalisaLabaRugi::updateOrCreate(
            ['bulan' => $this->bulan, 'tahun' => $this->tahun],
            [
                'total_pendapatan' => $this->totalPendapatan,
                'total_modal' => $this->totalModal,
                'total_beban' => $this->totalBeban,
                'laba_bersih' => $this->labaBersih,
                'status_keuangan' => $hasil['status_keuangan'],
                'ringkasan' => $hasil['ringkasan'],
                'analisis_pendapatan' => $hasil['analisis_pendapatan'],
                'analisis_beban' => $hasil['analisis_beban'],
                'analisis_margin' => $hasil['analisis_margin'],
                'rekomendasi' => $hasil['rekomendasi'],
                'kesimpulan' => $hasil['kesimpulan'],
                'raw_response' => $hasil['raw_response'] ?? null,
            ]
        );

        $this->loadAnalisa();

        Notification::make()
            ->title('Analisa AI selesai')
            ->body('Laporan laba rugi periode ' . $this->nama_bulan . ' ' . $this->tahun . ' berhasil dianalisa.')
            ->success()
            ->send();
    }

    public function kirimPertanyaan(): void
    {
        $teks = trim($this->pertanyaan);
        if ($teks === '') {
            return;
        }

        $riwayatSebelumnya = $this->chat;

        $this->chat[] = ['role' => 'user', 'text' => $teks];
        $this->pertanyaan = '';

        try {
            $jawaban = app(GeminiService::class)->jawabPertanyaan(
                $teks,
                [
                    'nama_bulan' => $this->nama_bulan,
                    'tahun' => $this->tahun,
                    'total_pendapatan' => $this->totalPendapatan,
                    'total_modal' => $this->totalModal,
                    'total_beban' => $this->totalBeban,
                    'laba_bersih' => $this->labaBersih,
                ],
                $riwayatSebelumnya,
            );
        } catch (\Throwable $e) {
            $jawaban = '⚠️ ' . $e->getMessage();
        }

        $this->chat[] = ['role' => 'ai', 'text' => $jawaban];
    }

    public function resetChat(): void
    {
        $this->chat = [];
    }
}