<x-filament-widgets::widget>
    <x-filament::section
        heading="Detail Analisis Kompetitor"
        description="Panel ringkas agar hasil AI tidak menumpuk dalam satu blok panjang."
    >
        @php
            $cards = [
                [
                    'title' => 'Posisi Kompetitor',
                    'icon' => 'heroicon-o-map-pin',
                    'content' => $analysis['competitor_position'] ?? '-',
                    'color' => 'primary',
                ],
                [
                    'title' => 'Insight Harga',
                    'icon' => 'heroicon-o-banknotes',
                    'content' => $analysis['pricing_insight'] ?? '-',
                    'color' => 'warning',
                ],
                [
                    'title' => 'Celah Layanan',
                    'icon' => 'heroicon-o-wrench-screwdriver',
                    'content' => $analysis['service_gap'] ?? '-',
                    'color' => 'info',
                ],
                [
                    'title' => 'Rekomendasi Marketing',
                    'icon' => 'heroicon-o-megaphone',
                    'content' => $analysis['marketing_recommendation'] ?? '-',
                    'color' => 'success',
                ],
                [
                    'title' => 'Strategi Pembeda',
                    'icon' => 'heroicon-o-finger-print',
                    'content' => $analysis['differentiation_strategy'] ?? '-',
                    'color' => 'danger',
                ],
            ];

            $lists = [
                'Kelebihan Kompetitor' => $analysis['strengths'] ?? [],
                'Kelemahan Kompetitor' => $analysis['weaknesses'] ?? [],
                'Peluang Untuk Usaha Saya' => $analysis['opportunities'] ?? [],
                'Ancaman Kompetitor' => $analysis['threats'] ?? [],
            ];

            $chartNotes = $analysis['chart_data']['notes'] ?? [];

            $iconColorClasses = [
                'primary' => 'text-primary-600 dark:text-primary-400',
                'warning' => 'text-warning-600 dark:text-warning-400',
                'info' => 'text-info-600 dark:text-info-400',
                'success' => 'text-success-600 dark:text-success-400',
                'danger' => 'text-danger-600 dark:text-danger-400',
            ];
        @endphp

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($cards as $card)
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-center gap-2">
                        <x-dynamic-component :component="$card['icon']" class="h-5 w-5 {{ $iconColorClasses[$card['color']] ?? $iconColorClasses['primary'] }}" />
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $card['title'] }}
                        </h3>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        {{ $card['content'] }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            @foreach($lists as $title => $items)
                <details class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900" open>
                    <summary class="cursor-pointer text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $title }}
                    </summary>
                    <ul class="mt-3 space-y-2">
                        @forelse($items as $item)
                            <li class="flex gap-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary-500"></span>
                                <span>{{ $item }}</span>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500 dark:text-gray-400">
                                Tidak ada poin dari AI.
                            </li>
                        @endforelse
                    </ul>
                </details>
            @endforeach
        </div>

        @if(! empty($chartNotes))
            <div class="mt-5 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-bar-square class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                        Catatan Visual
                    </h3>
                </div>
                <ul class="mt-3 grid gap-2 md:grid-cols-2">
                    @foreach($chartNotes as $note)
                        <li class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                            {{ $note }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
