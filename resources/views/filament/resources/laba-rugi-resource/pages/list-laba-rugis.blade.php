<x-filament::page>

    <style>[x-cloak]{display:none!important}</style>

    @php
        $rupiah = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        $namaBulan = $this->nama_bulan;
    @endphp

    <div class="space-y-6">

        <?php /* ============ FILTER PERIODE (selalu tampil) ============ */ ?>
        <x-filament::section>
            <x-slot name="heading">Pilih Periode</x-slot>
            <x-slot name="description">Pilih bulan dan tahun untuk melihat laporan laba rugi.</x-slot>

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

        <?php /* ============ TAB NAVIGASI + KONTEN ============ */ ?>
        <div x-data="{ tab: (localStorage.getItem('lrTab') || 'laporan') }" x-effect="localStorage.setItem('lrTab', tab)">

            <div class="mb-6 flex flex-wrap gap-1 border-b border-gray-200 dark:border-white/10">
                <button type="button" x-on:click="tab='laporan'"
                    :class="tab==='laporan' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition">
                    <span>📊</span> Laporan
                </button>
                <button type="button" x-on:click="tab='tren'"
                    :class="tab==='tren' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition">
                    <span>📈</span> Rekap Laba Per Bulan
                </button>
                <button type="button" x-on:click="tab='ai'"
                    :class="tab==='ai' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition">
                    <span>✨</span> Analisa AI
                </button>
            </div>

            <?php /* ---------- TAB: LAPORAN ---------- */ ?>
            <div x-show="tab==='laporan'">
                <x-filament::section>
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">BLaundry</h2>
                        <p class="font-semibold text-gray-700 dark:text-gray-200">Laporan Laba Rugi</p>
                        <p class="text-sm text-gray-500">Periode <?= $namaBulan ?> <?= $tahun ?></p>
                    </div>

                    <div class="mx-auto max-w-2xl">
                        <table class="w-full text-sm">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="py-2 font-bold uppercase text-gray-700 dark:text-gray-200" colspan="2">Pendapatan</td>
                            </tr>
                            @forelse ($rincianPendapatan as $item)
                                <tr>
                                    <td class="py-1 pl-6 text-gray-600 dark:text-gray-300"><?= e($item['nama_akun']) ?></td>
                                    <td class="py-1 text-right"><?= $rupiah($item['jumlah']) ?></td>
                                </tr>
                            @empty
                                <tr><td class="py-1 pl-6 text-gray-400" colspan="2">Tidak ada pendapatan</td></tr>
                            @endforelse
                            <tr class="border-b border-gray-200 dark:border-gray-700 font-semibold">
                                <td class="py-2 pl-3">Total Pendapatan</td>
                                <td class="py-2 text-right text-success-600"><?= $rupiah($totalPendapatan) ?></td>
                            </tr>

                            @if ($totalModal != 0)
                                <tr>
                                    <td class="py-2 pl-3 text-gray-600 dark:text-gray-300">Modal</td>
                                    <td class="py-2 text-right"><?= $rupiah($totalModal) ?></td>
                                </tr>
                            @endif

                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="pt-5 pb-2 font-bold uppercase text-gray-700 dark:text-gray-200" colspan="2">Beban / Biaya</td>
                            </tr>
                            @forelse ($rincianBeban as $item)
                                <tr>
                                    <td class="py-1 pl-6 text-gray-600 dark:text-gray-300"><?= e($item['nama_akun']) ?></td>
                                    <td class="py-1 text-right text-danger-600">(<?= $rupiah($item['jumlah']) ?>)</td>
                                </tr>
                            @empty
                                <tr><td class="py-1 pl-6 text-gray-400" colspan="2">Tidak ada beban</td></tr>
                            @endforelse
                            <tr class="border-b border-gray-200 dark:border-gray-700 font-semibold">
                                <td class="py-2 pl-3">Total Beban</td>
                                <td class="py-2 text-right text-danger-600">(<?= $rupiah($totalBeban) ?>)</td>
                            </tr>

                            <tr class="border-t-2 border-gray-400 dark:border-gray-500 text-lg font-bold">
                                <td class="py-3">LABA BERSIH</td>
                                <td class="py-3 text-right <?= $labaBersih >= 0 ? 'text-success-600' : 'text-danger-600' ?>">
                                    <?= $rupiah($labaBersih) ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="pt-1 text-xs text-gray-400" colspan="2">Margin laba: <?= $marginLaba ?>%</td>
                            </tr>
                        </table>

                        <div class="mt-5 rounded-lg py-2.5 text-center text-sm font-semibold ring-1
                            <?= $labaBersih >= 0
                                ? 'text-success-700 ring-success-200 bg-success-50 dark:text-success-400 dark:bg-success-500/10 dark:ring-success-500/30'
                                : 'text-danger-700 ring-danger-200 bg-danger-50 dark:text-danger-400 dark:bg-danger-500/10 dark:ring-danger-500/30' ?>">
                            <?= $labaBersih >= 0 ? '✅ Periode ini LABA (untung)' : '⚠️ Periode ini RUGI' ?>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <?php /* ---------- TAB:  ---------- */ ?>
            <div x-show="tab==='tren'" x-cloak class="space-y-6">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-m-arrow-trending-up" class="h-5 w-5 text-primary-500" />
                            Rekap Laba Per Bulan
                        </div>
                    </x-slot>

                    @php
                        $maxAbs = collect($trenLaba)->max(fn ($t) => abs($t['laba'])) ?: 1;
                    @endphp

                    <div class="space-y-3">
                        @foreach ($trenLaba as $t)
                            @php
                                $lebar = round(abs($t['laba']) / $maxAbs * 100);
                                $positif = $t['laba'] >= 0;
                            @endphp
                            <div class="flex items-center gap-3">
                                <div class="w-16 shrink-0 text-xs text-gray-500"><?= e($t['label']) ?></div>
                                <div class="flex-1 h-6 rounded bg-gray-100 dark:bg-white/5 overflow-hidden">
                                    <div class="h-full <?= $positif ? 'bg-success-500' : 'bg-danger-500' ?>"
                                        style="width: <?= max($lebar, 3) ?>%"></div>
                                </div>
                                <div class="w-32 shrink-0 text-right text-xs font-medium <?= $positif ? 'text-success-600' : 'text-danger-600' ?>">
                                    <?= $rupiah($t['laba']) ?>
                                </div>
                                <div class="w-16 shrink-0 text-right text-xs">
                                    @if (! is_null($t['perubahan']))
                                        <span class="<?= $t['perubahan'] >= 0 ? 'text-success-600' : 'text-danger-600' ?>">
                                            <?= $t['perubahan'] >= 0 ? '▲' : '▼' ?> <?= abs($t['perubahan']) ?>%
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>

            <?php /* ---------- TAB: ANALISA AI ---------- */ ?>
            <div x-show="tab==='ai'" x-cloak>
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-m-sparkles" class="h-5 w-5 text-warning-500" />
                            Analisa AI
                        </div>
                    </x-slot>
                    <x-slot name="description">
                        Analisis laporan laba rugi pada periode ini oleh AI.
                    </x-slot>

                    @if ($analisa)
                        @php
                            $statusMeta = match ($analisa['status_keuangan'] ?? null) {
                                'Sehat' => ['icon' => '🟢', 'text' => 'text-success-600 dark:text-success-400'],
                                'Perlu Perhatian' => ['icon' => '🟡', 'text' => 'text-warning-600 dark:text-warning-400'],
                                'Kritis' => ['icon' => '🔴', 'text' => 'text-danger-600 dark:text-danger-400'],
                                default => ['icon' => '⚪', 'text' => 'text-gray-600 dark:text-gray-300'],
                            };
                        @endphp

                        <div class="space-y-6">

                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-gray-50 px-5 py-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl"><?= $statusMeta['icon'] ?></span>
                                    <div>
                                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Status Keuangan</div>
                                        <div class="text-lg font-bold <?= $statusMeta['text'] ?>"><?= e($analisa['status_keuangan'] ?? '-') ?></div>
                                    </div>
                                </div>
                                @if ($analisaDibuatPada)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Dianalisa <?= e($analisaDibuatPada) ?></span>
                                @endif
                            </div>

                            @if (!empty($analisa['ringkasan']))
                                <div class="border-l-4 border-warning-400 pl-4">
                                    <div class="mb-1 flex items-center gap-1.5 text-sm font-semibold text-warning-600 dark:text-warning-400">
                                        <x-filament::icon icon="heroicon-m-light-bulb" class="h-4 w-4" />
                                        Ringkasan
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300"><?= e($analisa['ringkasan']) ?></p>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 items-stretch">
                                <div class="flex h-full flex-col rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                                    <div class="mb-2 flex items-center gap-2">
                                        <span class="text-lg">💰</span>
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">Pemasukan</span>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300"><?= e($analisa['analisis_pendapatan'] ?? '-') ?></p>
                                </div>
                                <div class="flex h-full flex-col rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                                    <div class="mb-2 flex items-center gap-2">
                                        <span class="text-lg">🧾</span>
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">Pengeluaran</span>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300"><?= e($analisa['analisis_beban'] ?? '-') ?></p>
                                </div>
                                <div class="flex h-full flex-col rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                                    <div class="mb-2 flex items-center gap-2">
                                        <span class="text-lg">📊</span>
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">Margin Laba</span>
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300"><?= e($analisa['analisis_margin'] ?? '-') ?></p>
                                </div>
                            </div>

                            @if (!empty($analisa['rekomendasi']))
                                <div>
                                    <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        <x-filament::icon icon="heroicon-m-check-badge" class="h-5 w-5 text-success-500" />
                                        Rekomendasi
                                    </div>
                                    <div class="space-y-2">
                                        @foreach ($analisa['rekomendasi'] as $i => $saran)
                                            @php
                                                $bersih = preg_replace('/^\s*\d+[\.\)]\s*/', '', $saran);
                                                $bersih = str_replace('**', '', $bersih);
                                            @endphp
                                            <div class="flex gap-3 rounded-lg bg-gray-50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-success-500 text-xs font-bold text-white"><?= $i + 1 ?></span>
                                                <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300"><?= e($bersih) ?></p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if (!empty($analisa['kesimpulan']))
                                <div class="border-l-4 border-primary-500 pl-4">
                                    <div class="mb-1 flex items-center gap-1.5 text-sm font-semibold text-primary-600 dark:text-primary-400">
                                        <x-filament::icon icon="heroicon-m-flag" class="h-4 w-4" />
                                        Kesimpulan
                                    </div>
                                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300"><?= e($analisa['kesimpulan']) ?></p>
                                </div>
                            @endif

                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center gap-3 py-12 text-center">
                            <div style="font-size: 3rem; line-height: 1;">✨</div>
                            <p class="text-gray-600 dark:text-gray-300">Belum ada analisa AI untuk periode <?= $namaBulan ?> <?= $tahun ?>.</p>
                            <p class="text-sm text-gray-400">Klik tombol <span class="font-semibold">"Analisa dengan AI"</span> di pojok kanan atas untuk membuatnya.</p>
                        </div>
                    @endif
                </x-filament::section>
            </div>

        </div>
    </div>

</x-filament::page>