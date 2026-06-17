<?php

namespace App\Filament\Resources\NeracaResource\Pages;

use App\Filament\Resources\NeracaResource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ListNeracas extends Page
{
    protected static string $resource = NeracaResource::class;

    protected static string $view =
        'filament.resources.neraca-resource.pages.list-neracas';

    protected ?string $heading = 'Neraca';

    // Filter periode
    public int $bulan;
    public int $tahun;

    // Rincian per akun
    public array $asetLancar = [];
    public array $asetTetap = [];
    public array $kewajiban = [];
    public array $modal = [];

    // Total
    public float $totalAsetLancar = 0;
    public float $totalAsetTetap = 0;
    public float $totalAset = 0;
    public float $totalKewajiban = 0;
    public float $totalModal = 0;
    public float $labaBerjalan = 0;
    public float $totalEkuitas = 0;
    public float $totalKewajibanEkuitas = 0;
    public float $selisih = 0;
    public bool $seimbang = true;

    // Rasio keuangan (persen)
    public array $rasio = [];

    public function mount(): void
    {
        $this->bulan = (int) now()->month;
        $this->tahun = (int) now()->year;
        $this->loadData();
    }

    public function updatedBulan(): void
    {
        $this->loadData();
    }

    public function updatedTahun(): void
    {
        $this->loadData();
    }

    /**
     * Tanggal akhir periode terpilih (neraca bersifat kumulatif s/d tanggal ini).
     */
    private function tanggalSampai(): string
    {
        return Carbon::create($this->tahun, $this->bulan, 1)->endOfMonth()->toDateString();
    }

    /**
     * Query dasar jurnal_detail s/d akhir periode terpilih (kumulatif).
     */
    private function base()
    {
        return DB::table('jurnal_detail')
            ->join('akuncoa', 'akuncoa.id', '=', 'jurnal_detail.coa_id')
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
            ->whereDate('jurnal.tgl', '<=', $this->tanggalSampai());
    }

    /**
     * Saldo per akun untuk satu header_akun.
     *  - $tipe = 'debit'  -> saldo normal debit (Aset)            : debit - credit
     *  - $tipe = 'credit' -> saldo normal credit (Kewajiban/Modal): credit - debit
     */
    private function saldoPerAkun(int $header, string $tipe): array
    {
        $rumus = $tipe === 'debit'
            ? 'SUM(jurnal_detail.debit) - SUM(jurnal_detail.credit)'
            : 'SUM(jurnal_detail.credit) - SUM(jurnal_detail.debit)';

        return $this->base()
            ->where('akuncoa.header_akun', $header)
            ->groupBy('akuncoa.nama_akun')
            ->selectRaw('akuncoa.nama_akun as nama_akun, ' . $rumus . ' as saldo')
            ->havingRaw($rumus . ' <> 0')
            ->get()
            ->map(fn ($r) => [
                'nama_akun' => $r->nama_akun,
                'saldo' => (float) $r->saldo,
            ])
            ->toArray();
    }

    /**
     * Total bersih untuk satu header_akun (tanpa rincian).
     */
    private function totalHeader(int $header, string $tipe): float
    {
        $rumus = $tipe === 'debit'
            ? 'SUM(jurnal_detail.debit) - SUM(jurnal_detail.credit)'
            : 'SUM(jurnal_detail.credit) - SUM(jurnal_detail.debit)';

        $row = $this->base()
            ->where('akuncoa.header_akun', $header)
            ->selectRaw($rumus . ' as nilai')
            ->first();

        return (float) ($row->nilai ?? 0);
    }

    private function loadData(): void
    {
        // Aset (header 1) -> saldo normal debit, dipisah Lancar vs Tetap.
        // Aturan: kode akun diawali '12' = Aset Tetap, selainnya = Aset Lancar.
        $rumusAset = 'SUM(jurnal_detail.debit) - SUM(jurnal_detail.credit)';
        $asetRows = $this->base()
            ->where('akuncoa.header_akun', 1)
            ->groupBy('akuncoa.nama_akun', 'akuncoa.kode_akun')
            ->selectRaw('akuncoa.nama_akun as nama_akun, akuncoa.kode_akun as kode_akun, ' . $rumusAset . ' as saldo')
            ->havingRaw($rumusAset . ' <> 0')
            ->get();

        $this->asetLancar = [];
        $this->asetTetap = [];
        foreach ($asetRows as $r) {
            $item = ['nama_akun' => $r->nama_akun, 'saldo' => (float) $r->saldo];
            if (str_starts_with((string) $r->kode_akun, '12')) {
                $this->asetTetap[] = $item;
            } else {
                $this->asetLancar[] = $item;
            }
        }

        $this->totalAsetLancar = (float) array_sum(array_column($this->asetLancar, 'saldo'));
        $this->totalAsetTetap = (float) array_sum(array_column($this->asetTetap, 'saldo'));
        $this->totalAset = $this->totalAsetLancar + $this->totalAsetTetap;

        // Kewajiban (header 2) -> saldo normal credit
        $this->kewajiban = $this->saldoPerAkun(2, 'credit');
        $this->totalKewajiban = (float) array_sum(array_column($this->kewajiban, 'saldo'));

        // Modal (header 3) -> saldo normal credit
        $this->modal = $this->saldoPerAkun(3, 'credit');
        $this->totalModal = (float) array_sum(array_column($this->modal, 'saldo'));

        // Laba berjalan akumulasi = Pendapatan (4) - Beban (5)
        $pendapatan = $this->totalHeader(4, 'credit');
        $beban = $this->totalHeader(5, 'debit');
        $this->labaBerjalan = $pendapatan - $beban;

        $this->totalEkuitas = $this->totalModal + $this->labaBerjalan;
        $this->totalKewajibanEkuitas = $this->totalKewajiban + $this->totalEkuitas;

        $this->selisih = round($this->totalAset - $this->totalKewajibanEkuitas, 2);
        $this->seimbang = abs($this->selisih) < 1;

        $this->hitungRasio();
    }

    /**
     * Hitung rasio keuangan (dalam persen) dari angka neraca.
     */
    private function hitungRasio(): void
    {
        $this->rasio = [
            'debt_to_asset' => $this->totalAset != 0
                ? round($this->totalKewajiban / $this->totalAset * 100, 1) : 0,
            'der' => $this->totalEkuitas != 0
                ? round($this->totalKewajiban / $this->totalEkuitas * 100, 1) : 0,
            'equity_ratio' => $this->totalAset != 0
                ? round($this->totalEkuitas / $this->totalAset * 100, 1) : 0,
            'roa' => $this->totalAset != 0
                ? round($this->labaBerjalan / $this->totalAset * 100, 1) : 0,
            'roe' => $this->totalEkuitas != 0
                ? round($this->labaBerjalan / $this->totalEkuitas * 100, 1) : 0,
        ];
    }

    public function getNamaBulanProperty(): string
    {
        return Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F');
    }

    public function getTanggalNeracaProperty(): string
    {
        return Carbon::create($this->tahun, $this->bulan, 1)->endOfMonth()->translatedFormat('d F Y');
    }
}