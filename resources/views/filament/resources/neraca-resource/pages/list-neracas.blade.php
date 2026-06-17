<x-filament::page>

    @php
        $rupiah = fn ($n) => $n < 0
            ? '(Rp ' . number_format(abs($n), 0, ',', '.') . ')'
            : 'Rp ' . number_format($n, 0, ',', '.');
    @endphp

    <div class="space-y-6">

        <?php /* ============ FILTER PERIODE ============ */ ?>
        <x-filament::section>
            <x-slot name="heading">Pilih Periode</x-slot>
            <x-slot name="description">Neraca menampilkan saldo akun secara kumulatif sampai akhir bulan terpilih.</x-slot>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:max-w-md">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Bulan</label>
                    <select wire:model.live="bulan"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200">
                        @foreach (range(1, 12) as $m)
                            <option value="<?= $m ?>">
                                <?= \Illuminate\Support\Carbon::create(null, $m, 1)->translatedFormat('F') ?>
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tahun</label>
                    <select wire:model.live="tahun"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200">
                        @foreach (range(now()->year, now()->year - 5) as $y)
                            <option value="<?= $y ?>"><?= $y ?></option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::section>

        <?php /* ============ NERACA ============ */ ?>
        <x-filament::section>
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">BLaundry</h2>
                <p class="font-semibold text-gray-700 dark:text-gray-200">Neraca (Laporan Posisi Keuangan)</p>
                <p class="text-sm text-gray-500">Per <?= $this->tanggal_neraca ?></p>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">

                <?php /* ----- ASET ----- */ ?>
                <div>
                    <table class="w-full text-sm">
                        <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                            <td class="py-2 font-bold uppercase tracking-wide text-gray-700 dark:text-gray-200" colspan="2">Aset</td>
                        </tr>

                        <?php /* -- Aset Lancar -- */ ?>
                        <tr>
                            <td class="pt-3 pb-1 pl-2 font-semibold text-gray-700 dark:text-gray-200" colspan="2">Aset Lancar</td>
                        </tr>
                        @forelse ($asetLancar as $a)
                            <tr>
                                <td class="py-1 pl-6 text-gray-600 dark:text-gray-300"><?= e($a['nama_akun']) ?></td>
                                <td class="py-1 text-right text-gray-700 dark:text-gray-200"><?= $rupiah($a['saldo']) ?></td>
                            </tr>
                        @empty
                            <tr><td class="py-1 pl-6 text-gray-400" colspan="2">Belum ada aset lancar</td></tr>
                        @endforelse
                        <tr class="border-b border-gray-200 dark:border-gray-700 font-semibold">
                            <td class="py-1 pl-4">Total Aset Lancar</td>
                            <td class="py-1 text-right"><?= $rupiah($totalAsetLancar) ?></td>
                        </tr>

                        @if (count($asetTetap) > 0)
                        <?php /* -- Aset Tetap (tampil hanya jika ada isinya) -- */ ?>
                        <tr>
                            <td class="pt-4 pb-1 pl-2 font-semibold text-gray-700 dark:text-gray-200" colspan="2">Aset Tetap</td>
                        </tr>
                        @foreach ($asetTetap as $a)
                            <tr>
                                <td class="py-1 pl-6 text-gray-600 dark:text-gray-300"><?= e($a['nama_akun']) ?></td>
                                <td class="py-1 text-right text-gray-700 dark:text-gray-200"><?= $rupiah($a['saldo']) ?></td>
                            </tr>
                        @endforeach
                        <tr class="border-b border-gray-200 dark:border-gray-700 font-semibold">
                            <td class="py-1 pl-4">Total Aset Tetap</td>
                            <td class="py-1 text-right"><?= $rupiah($totalAsetTetap) ?></td>
                        </tr>
                        @endif

                        <tr class="border-t-2 border-gray-400 dark:border-gray-500 font-bold">
                            <td class="py-2">TOTAL ASET</td>
                            <td class="py-2 text-right text-primary-600"><?= $rupiah($totalAset) ?></td>
                        </tr>
                    </table>
                </div>

                <?php /* ----- KEWAJIBAN & EKUITAS ----- */ ?>
                <div>
                    <table class="w-full text-sm">
                        <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                            <td class="py-2 font-bold uppercase tracking-wide text-gray-700 dark:text-gray-200" colspan="2">Kewajiban</td>
                        </tr>
                        @forelse ($kewajiban as $k)
                            <tr>
                                <td class="py-1 pl-4 text-gray-600 dark:text-gray-300"><?= e($k['nama_akun']) ?></td>
                                <td class="py-1 text-right text-gray-700 dark:text-gray-200"><?= $rupiah($k['saldo']) ?></td>
                            </tr>
                        @empty
                            <tr><td class="py-1 pl-4 text-gray-400" colspan="2">Tidak ada kewajiban</td></tr>
                        @endforelse
                        <tr class="border-b border-gray-200 dark:border-gray-700 font-semibold">
                            <td class="py-1 pl-2">Total Kewajiban</td>
                            <td class="py-1 text-right"><?= $rupiah($totalKewajiban) ?></td>
                        </tr>

                        <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                            <td class="pt-5 pb-2 font-bold uppercase tracking-wide text-gray-700 dark:text-gray-200" colspan="2">Ekuitas / Modal</td>
                        </tr>
                        @foreach ($modal as $mo)
                            <tr>
                                <td class="py-1 pl-4 text-gray-600 dark:text-gray-300"><?= e($mo['nama_akun']) ?></td>
                                <td class="py-1 text-right text-gray-700 dark:text-gray-200"><?= $rupiah($mo['saldo']) ?></td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="py-1 pl-4 text-gray-600 dark:text-gray-300">Laba Periode Berjalan</td>
                            <td class="py-1 text-right <?= $labaBerjalan >= 0 ? 'text-success-600' : 'text-danger-600' ?>"><?= $rupiah($labaBerjalan) ?></td>
                        </tr>
                        <tr class="border-b border-gray-200 dark:border-gray-700 font-semibold">
                            <td class="py-1 pl-2">Total Ekuitas</td>
                            <td class="py-1 text-right"><?= $rupiah($totalEkuitas) ?></td>
                        </tr>

                        <tr class="border-t-2 border-gray-400 dark:border-gray-500 font-bold">
                            <td class="py-2">TOTAL KEWAJIBAN + EKUITAS</td>
                            <td class="py-2 text-right text-primary-600"><?= $rupiah($totalKewajibanEkuitas) ?></td>
                        </tr>
                    </table>
                </div>

            </div>

            <?php /* ----- CEK KESEIMBANGAN ----- */ ?>
            <div class="mt-6 rounded-lg py-2.5 text-center text-sm font-semibold ring-1
                <?= $seimbang
                    ? 'text-success-700 ring-success-200 bg-success-50 dark:text-success-400 dark:bg-success-500/10 dark:ring-success-500/30'
                    : 'text-danger-700 ring-danger-200 bg-danger-50 dark:text-danger-400 dark:bg-danger-500/10 dark:ring-danger-500/30' ?>">
                <?= $seimbang
                    ? '✅ Neraca seimbang — Aset = Kewajiban + Ekuitas'
                    : '⚠️ Neraca belum seimbang — ada selisih ' . $rupiah(abs($selisih)) . '. Cek kembali jurnalnya.' ?>
            </div>
        </x-filament::section>

    </div>

</x-filament::page>