@php
    if (!$pemesanan) {
        $noData = true;
    } else {
        $noData      = false;
        $details     = $pemesanan->detailPemesanan ?? collect();
        $ongkir      = (float) ($pemesanan->ongkir ?? 0);
        $subtotalSum = $details->sum('subtotal');
        $grandTotal  = (float) $pemesanan->total_harga;
    }
@endphp

@if($noData)
    <div class="flex flex-col items-center justify-center py-10 text-gray-400 gap-2">
        <x-heroicon-o-exclamation-circle class="w-10 h-10" />
        <p class="text-sm font-medium">Pesanan belum diproses.</p>
        <p class="text-xs">Kembali ke Step 3 dan klik <strong>Proses Pemesanan</strong> terlebih dahulu.</p>
    </div>
@else
<div class="space-y-4">

    {{-- ── Info Pesanan ── --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-orange-500 to-orange-400 px-5 py-3 flex items-center gap-2">
            <x-heroicon-o-shopping-bag class="w-4 h-4 text-white" />
            <h2 class="text-white font-semibold text-sm tracking-wide">Ringkasan Pesanan</h2>
        </div>
        <div class="px-5 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-gray-400 text-xs mb-0.5">Kode Pesanan</p>
                <p class="font-bold text-gray-800">{{ $pemesanan->kode_pemesanan }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-0.5">Pelanggan</p>
                <p class="font-semibold text-gray-800">{{ $pemesanan->member->nama_pelanggan ?? '-' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-0.5">Tanggal Pesan</p>
                <p class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($pemesanan->tgl_pesan)->format('d M Y') }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs mb-0.5">Pengantaran</p>
                <p class="font-semibold text-gray-800 capitalize">{{ $pemesanan->pengantaran }}</p>
            </div>
        </div>
    </div>

    {{-- ── Tabel Detail Layanan (scrollable) ── --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
            <x-heroicon-o-list-bullet class="w-4 h-4 text-orange-500" />
            <h3 class="font-semibold text-sm text-gray-700">Detail Layanan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Layanan</th>
                        <th class="px-4 py-3 text-left">Kategori</th>
                        <th class="px-4 py-3 text-center">Jumlah</th>
                        <th class="px-4 py-3 text-right">Harga Satuan</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($details as $detail)
                        @php
                            $isSatuan = $detail->layanan?->kategoriLayanan?->nama_kategori === 'Satuan';
                        @endphp
                        <tr class="hover:bg-orange-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $detail->nama_layanan ?? $detail->layanan?->nama_layanan ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($isSatuan)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Satuan</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">Kiloan</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700">
                                {{ number_format($detail->berat_kg, 2) }} {{ $isSatuan ? 'pcs' : 'kg' }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600">
                                Rp{{ number_format($detail->harga_per_kg, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                Rp{{ number_format($detail->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-400 text-xs">
                                Tidak ada detail layanan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 text-sm border-t border-gray-200">
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right text-gray-500">Subtotal Layanan</td>
                        <td class="px-4 py-2 text-right font-semibold text-gray-700">
                            Rp{{ number_format($subtotalSum, 0, ',', '.') }}
                        </td>
                    </tr>
                    @if($ongkir > 0)
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right text-gray-500">Ongkos Kirim</td>
                        <td class="px-4 py-2 text-right font-semibold text-gray-700">
                            Rp{{ number_format($ongkir, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ── Total Tagihan (highlight) ── --}}
    <div class="rounded-xl border-2 border-orange-400 bg-orange-50 px-5 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-orange-400 flex items-center justify-center shrink-0">
                <x-heroicon-o-banknotes class="w-5 h-5 text-white" />
            </div>
            <div>
                <p class="text-xs text-orange-500 font-medium uppercase tracking-wide">Total Tagihan</p>
                <p class="text-2xl font-bold text-orange-600">Rp{{ number_format($grandTotal, 0, ',', '.') }}</p>
            </div>
        </div>
        @if($pemesanan->pembayaran)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-green-100 text-green-700 text-xs font-bold uppercase">
                <x-heroicon-o-check-circle class="w-4 h-4" /> LUNAS
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-red-100 text-red-500 text-xs font-bold uppercase">
                <x-heroicon-o-clock class="w-4 h-4" /> BELUM LUNAS
            </span>
        @endif
    </div>

</div>
@endif
