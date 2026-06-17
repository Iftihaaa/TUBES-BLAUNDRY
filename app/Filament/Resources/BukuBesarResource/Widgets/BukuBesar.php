<?php

namespace App\Filament\Resources\BukuBesarResource\Widgets;

use Filament\Widgets\Widget;

// tambahan
use App\Models\Jurnal;
use App\Models\AkunCoa;
use Carbon\Carbon;

class BukuBesar extends Widget
{
    protected static string $view = 'filament.resources.buku-besar-resource.widgets.buku-besar';

    // tambahan
    protected int | string | array $columnSpan = 'full';

    public $periode_awal;
    public $periode_akhir;
    public $coa_id; // akun yang dipilih

    protected $listeners = ['filterUpdated' => 'getViewData'];

    public function mount(): void
    {
        // Default periode awal = bulan ini
        $this->periode_awal = request('periode_awal', now()->format('Y-m'));
        $this->periode_akhir = request('periode_akhir', now()->format('Y-m'));
        $this->coa_id = request('coa_id'); // default null
    }

    public function filterJurnal(): void
    {
        // Di sini kalau mau trigger refresh manual
        // $this->emit('filterUpdated');
    }

    /**
     * Cek apakah akun ini saldo normal Debit.
     * header_akun 1 = Aset, 5 = Beban → Debit normal
     * header_akun 2 = Liabilitas, 3 = Ekuitas, 4 = Pendapatan → Kredit normal
     */
    private function isDebitNormal(): bool
    {
        if (!$this->coa_id) return true;
        $coa = AkunCoa::find($this->coa_id);
        return in_array($coa?->header_akun, [1, 5]);
    }

    public function getViewData(): array
    {
        $saldoAwal = 0;
        $isDebitNormal = $this->isDebitNormal();

        $jurnalsQuery = Jurnal::with(['jurnaldetail' => function ($query) {
            if ($this->coa_id) {
                $query->where('coa_id', $this->coa_id);
            }
            $query->with('coa');
        }])
        ->orderBy('tgl', 'asc')
        ->orderBy('id', 'asc');

        if ($this->periode_awal && $this->periode_akhir) {
            $awal = Carbon::createFromFormat('Y-m', $this->periode_awal)->startOfMonth();
            $akhir = Carbon::createFromFormat('Y-m', $this->periode_akhir)->endOfMonth();

            // Hitung saldo awal dari transaksi SEBELUM periode_awal
            $detailSebelumPeriode = Jurnal::where('tgl', '<', $awal)
                ->with(['jurnaldetail' => function ($query) {
                    $query->where('coa_id', $this->coa_id);
                }])
                ->get()
                ->flatMap->jurnaldetail;

            $totalDebitAwal  = $detailSebelumPeriode->sum('debit');
            $totalKreditAwal = $detailSebelumPeriode->sum('credit');

            // Saldo awal sesuai saldo normal akun
            $saldoAwal = $isDebitNormal
                ? $totalDebitAwal - $totalKreditAwal
                : $totalKreditAwal - $totalDebitAwal;

            $jurnalsQuery->whereBetween('tgl', [$awal, $akhir]);
        }

        $jurnals = $jurnalsQuery->get();

        return [
            'jurnals'       => $jurnals,
            'periode_awal'  => $this->periode_awal,
            'periode_akhir' => $this->periode_akhir,
            'saldoAwal'     => $saldoAwal,
            'isDebitNormal' => $isDebitNormal,
        ];
    }
}