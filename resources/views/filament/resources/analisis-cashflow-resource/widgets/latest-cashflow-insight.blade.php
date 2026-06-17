<x-filament-widgets::widget class="h-full">
    <x-filament::section class="h-full">
        <x-slot name="heading">💡 Insight AI Terbaru</x-slot>

        @php $data = $this->getLatestAnalisis() @endphp

        @if ($data)
            <div class="space-y-4 text-sm">
                <div>
                    <p class="font-semibold text-gray-500 uppercase tracking-wide text-xs mb-1">
                        Periode
                    </p>
                    <p class="font-bold text-primary-600">
                        {{ \Carbon\Carbon::parse($data->periode)->format('F Y') }}
                    </p>
                </div>

                <div>
                    <p class="font-semibold text-gray-500 uppercase tracking-wide text-xs mb-1">
                        Kesimpulan
                    </p>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ $data->kesimpulan }}
                    </p>
                </div>

                <div>
                    <p class="font-semibold text-gray-500 uppercase tracking-wide text-xs mb-1">
                        Saran Operasional
                    </p>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ $data->saran_operasional }}
                    </p>
                </div>
            </div>
        @else
            <p class="text-gray-400 text-sm italic">
                Belum ada analisis. Klik tombol "Analisis AI Cashflow" untuk memulai.
            </p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>