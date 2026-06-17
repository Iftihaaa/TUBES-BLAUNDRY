<x-filament-panels::page>
    <div class="space-y-6">
        @if(isset($latest) && $latest)
            {{-- Kartu Analisis AI --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-2xl">🤖</span>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                        {{ $latest->nama_tren }}
                    </h2>
                    <span class="ml-auto text-xs text-gray-400">
                        {{ $latest->created_at->diffForHumans() }}
                    </span>
                </div>

                <p class="text-gray-600 dark:text-gray-300 mb-4">
                    {{ $latest->analisis_ai }}
                </p>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
                        <p class="text-xs text-amber-600 font-semibold uppercase">Aroma Terpopuler</p>
                        <p class="text-lg font-bold text-amber-700 dark:text-amber-400">
                            {{ $latest->aroma_terpopuler }}
                        </p>
                    </div>
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                        <p class="text-xs text-indigo-600 font-semibold uppercase">Rekomendasi AI</p>
                        <p class="text-sm font-medium text-indigo-700 dark:text-indigo-400">
                            {{ $latest->rekomendasi }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Top 10 Parfum dengan warna --}}
            @if($latest->parfum_populer)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-md font-bold text-gray-800 dark:text-white mb-4">
                    📊 Top 10 Parfum Laundry Populer
                </h3>
                <div class="space-y-3">
                    @php
                    $warnaBar = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#0ea5e9','#ec4899','#eab308','#14b8a6','#f97316'];
                    @endphp
                    @foreach($latest->parfum_populer as $idx => $parfum)
                    @php $warna = $warnaBar[$idx % count($warnaBar)]; @endphp
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300 w-36 shrink-0">
                            {{ $parfum['nama'] }}
                        </span>
                        <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-lg h-7 overflow-hidden">
                            <div class="h-7 rounded-lg flex items-center px-3 transition-all duration-500"
                                 style="width: {{ ($parfum['skor'] / 10) * 100 }}%; background-color: {{ $warna }}">
                                <span class="text-white text-xs font-bold drop-shadow">
                                    {{ $parfum['skor'] }}/10
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        @else
            {{-- Belum ada data --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-12 text-center border border-gray-200 dark:border-gray-700">
                <span class="text-6xl">🤖</span>
                <h3 class="text-lg font-bold text-gray-700 dark:text-white mt-4">
                    Belum Ada Data Tren Parfum
                </h3>
                <p class="text-gray-500 mt-2">
                    Klik tombol "Refresh Tren Parfum" di atas untuk memulai analisis Gemini AI
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>