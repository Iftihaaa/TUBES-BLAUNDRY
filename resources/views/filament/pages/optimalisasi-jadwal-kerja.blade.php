<x-filament-panels::page>
    <div class="space-y-6">

        {{-- TOMBOL GENERATE --}}
        <div class="flex items-center gap-4">
            <x-filament::button
                wire:click="analisis"
                wire:loading.attr="disabled"
                size="lg"
                icon="heroicon-o-sparkles"
            >
                <span wire:loading.remove wire:target="analisis">🤖 Generate Jadwal dengan AI</span>
                <span wire:loading wire:target="analisis">⏳ AI sedang menganalisis data...</span>
            </x-filament::button>

            <span wire:loading wire:target="analisis" class="text-sm text-gray-500 dark:text-gray-400 animate-pulse">
                Mohon tunggu, proses ini memerlukan beberapa detik...
            </span>
        </div>

        {{-- PESAN ERROR --}}
        @if($errorPesan)
            <div class="rounded-xl border border-danger-200 bg-danger-50 dark:bg-danger-950 dark:border-danger-800 p-4 text-danger-700 dark:text-danger-300 flex items-start gap-3">
                <x-heroicon-o-exclamation-circle class="w-5 h-5 mt-0.5 shrink-0" />
                <span class="text-sm">{{ $errorPesan }}</span>
            </div>
        @endif

        {{-- HASIL AI --}}
        @if(!empty($jadwal))

            {{-- KARTU RINGKASAN --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- Peak Day --}}
                <div class="rounded-xl border border-warning-200 bg-warning-50 dark:bg-warning-950 dark:border-warning-800 p-4 flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-warning-100 dark:bg-warning-900 flex items-center justify-center">
                        <x-heroicon-o-fire class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-warning-600 dark:text-warning-400">Hari Tersibuk</p>
                        <p class="text-xl font-bold text-warning-800 dark:text-warning-200">{{ $peakDay }}</p>
                    </div>
                </div>

                {{-- Saran Shift --}}
                <div class="rounded-xl border border-primary-200 bg-primary-50 dark:bg-primary-950 dark:border-primary-800 p-4 flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                        <x-heroicon-o-light-bulb class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">Saran Shift</p>
                        <p class="text-sm font-medium text-primary-800 dark:text-primary-200 leading-snug">{{ $saranShift ?: '-' }}</p>
                    </div>
                </div>

                {{-- Ringkasan --}}
                <div class="rounded-xl border border-success-200 bg-success-50 dark:bg-success-950 dark:border-success-800 p-4 flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-success-100 dark:bg-success-900 flex items-center justify-center">
                        <x-heroicon-o-chat-bubble-left-right class="w-6 h-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-success-600 dark:text-success-400">Ringkasan AI</p>
                        <p class="text-sm font-medium text-success-800 dark:text-success-200 leading-snug">{{ $ringkasan ?: '-' }}</p>
                    </div>
                </div>

            </div>

            {{-- TABEL JADWAL MINGGUAN --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                <div class="bg-gray-50 dark:bg-gray-800 px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                    <x-heroicon-o-calendar-days class="w-5 h-5 text-primary-500" />
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">
                        Jadwal Kerja Minggu Depan
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-700 text-left">
                                <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300 w-24">Hari</th>
                                <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Volume</th>
                                <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Pegawai & Shift</th>
                                <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-300">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($jadwal as $index => $hari)
                                @php
                                    $isEven = $index % 2 === 0;
                                    $rowBg  = $isEven ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/50 dark:bg-gray-800/50';

                                    $volume     = strtolower($hari['volume'] ?? 'sedang');
                                    $volumeMap  = [
                                        'rendah' => [
                                            'label' => 'Rendah',
                                            'badge' => 'bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300',
                                            'dot'   => 'bg-success-500',
                                        ],
                                        'sedang' => [
                                            'label' => 'Sedang',
                                            'badge' => 'bg-warning-100 text-warning-700 dark:bg-warning-900 dark:text-warning-300',
                                            'dot'   => 'bg-warning-500',
                                        ],
                                        'tinggi' => [
                                            'label' => 'Tinggi',
                                            'badge' => 'bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300',
                                            'dot'   => 'bg-danger-500',
                                        ],
                                    ];
                                    $v = $volumeMap[$volume] ?? $volumeMap['sedang'];
                                @endphp
                                <tr class="{{ $rowBg }} hover:bg-primary-50/30 dark:hover:bg-primary-900/10 transition-colors">

                                    {{-- Hari --}}
                                    <td class="px-4 py-4">
                                        <span class="font-bold text-gray-800 dark:text-gray-100">
                                            {{ $hari['hari'] ?? '-' }}
                                        </span>
                                    </td>

                                    {{-- Volume Badge --}}
                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $v['badge'] }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $v['dot'] }}"></span>
                                            {{ $v['label'] }}
                                        </span>
                                    </td>

                                    {{-- Pegawai & Shift --}}
                                    <td class="px-4 py-4">
                                        @if(!empty($hari['pegawai']))
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($hari['pegawai'] as $p)
                                                    <div class="inline-flex items-center gap-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-1.5 shadow-sm">
                                                        <div class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center shrink-0">
                                                            <span class="text-xs font-bold text-primary-700 dark:text-primary-300">
                                                                {{ strtoupper(substr($p['nama'] ?? '?', 0, 1)) }}
                                                            </span>
                                                        </div>
                                                        <div class="leading-none">
                                                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-100">{{ $p['nama'] ?? '-' }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $p['jabatan'] ?? '' }}</p>
                                                            <p class="text-xs font-mono text-primary-600 dark:text-primary-400 mt-0.5">
                                                                {{ $p['jam_masuk'] ?? '-' }} – {{ $p['jam_keluar'] ?? '-' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs italic">Tidak ada jadwal</span>
                                        @endif
                                    </td>

                                    {{-- Catatan --}}
                                    <td class="px-4 py-4 text-gray-500 dark:text-gray-400 text-xs leading-relaxed max-w-xs">
                                        {{ $hari['catatan'] ?? '-' }}
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- CATATAN KHUSUS PEGAWAI --}}
            @if(!empty($catatanPegawai))
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                    <div class="bg-gray-50 dark:bg-gray-800 px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                        <x-heroicon-o-user-group class="w-5 h-5 text-warning-500" />
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">
                            Catatan Khusus Pegawai
                        </h2>
                    </div>
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($catatanPegawai as $cp)
                            <div class="flex items-start gap-3 bg-warning-50 dark:bg-warning-950 border border-warning-200 dark:border-warning-800 rounded-lg p-3">
                                <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-warning-500 shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-xs font-bold text-warning-800 dark:text-warning-200">{{ $cp['nama'] ?? '-' }}</p>
                                    <p class="text-xs text-warning-700 dark:text-warning-300 mt-0.5">{{ $cp['catatan'] ?? '-' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        @endif

    </div>
</x-filament-panels::page>