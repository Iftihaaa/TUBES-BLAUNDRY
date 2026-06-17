<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Analisis Kompetitor Laundry
        </x-slot>

        <x-slot name="description">
            Ringkasan AI dari data kompetitor yang diinput admin.
        </x-slot>

        @if(! $hasAnalysis)
            <div class="flex flex-col gap-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-300">
                        <x-heroicon-o-sparkles class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                            Belum ada hasil analisis kompetitor
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                            Klik tombol Analisis Kompetitor di kanan atas dashboard, isi data nyata yang diketahui, lalu hasil AI akan tampil di sini.
                        </p>
                    </div>
                </div>
            </div>
        @else
            @php
                $input = $analysis['input'] ?? [];
                $summary = $analysis['executive_summary'] ?? [];
            @endphp

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(280px,0.7fr)]">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase text-primary-600 dark:text-primary-400">
                                Ringkasan Eksekutif
                            </p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $input['nama_kompetitor'] ?? 'Kompetitor' }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $input['alamat_lokasi'] ?? '-' }}
                            </p>
                        </div>

                        <div class="rounded-md bg-gray-100 px-3 py-2 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            {{ $analysis['generated_at'] ?? '' }}
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @foreach($summary as $point)
                            <div class="flex gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                                <x-heroicon-o-check-circle class="mt-0.5 h-5 w-5 shrink-0 text-success-500" />
                                <p class="text-sm leading-6 text-gray-700 dark:text-gray-200">
                                    {{ $point }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-primary-200 bg-primary-50 p-5 dark:border-primary-800 dark:bg-primary-950">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-flag class="h-5 w-5 text-primary-600 dark:text-primary-300" />
                        <p class="text-sm font-semibold text-primary-800 dark:text-primary-200">
                            Rekomendasi Utama
                        </p>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-primary-900 dark:text-primary-100">
                        {{ $analysis['final_recommendation'] ?? '-' }}
                    </p>
                    <p class="mt-4 border-t border-primary-200 pt-3 text-xs leading-5 text-primary-700 dark:border-primary-800 dark:text-primary-300">
                        {{ $analysis['confidence_note'] ?? 'Analisis ini berbasis input yang dimasukkan user.' }}
                    </p>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
