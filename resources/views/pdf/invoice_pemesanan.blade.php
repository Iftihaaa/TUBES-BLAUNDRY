<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 20px; color: #4f46e5; }
        .header p { margin: 4px 0; font-size: 12px; color: #666; }
        .info-table { width: 100%; margin-bottom: 16px; }
        .info-table td { padding: 4px 8px; }
        .info-table .label { font-weight: bold; width: 40%; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.items th, table.items td { border: 1px solid #ccc; padding: 6px 8px; }
        table.items th { background-color: #4f46e5; color: white; text-align: left; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; background-color: #f2f2f2; }
        .footer { margin-top: 24px; font-size: 11px; color: #999; text-align: center; }
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 11px; background-color: #d1fae5; color: #065f46; }
    </style>
</head>
<body>

    <div class="header">
        <h2>BLaundry</h2>
        <p>Invoice Pembayaran</p>
        <p>Dicetak pada: {{ $tanggal }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Kode Pemesanan</td>
            <td>: {{ $kode_pemesanan }}</td>
        </tr>
        <tr>
            <td class="label">Nama Pelanggan</td>
            <td>: {{ $nama_pelanggan }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Pesan</td>
            <td>: {{ \Carbon\Carbon::parse($tgl_pesan)->format('d-M-Y') }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Bayar</td>
            <td>: {{ \Carbon\Carbon::parse($tgl_bayar)->format('d-M-Y') }}</td>
        </tr>
        <tr>
            <td class="label">Metode Pembayaran</td>
            <td>: {{ ucfirst($jenis_pembayaran) }}</td>
        </tr>
        <tr>
            <td class="label">Status Pembayaran</td>
            <td>: <span class="badge">{{ $status_message }}</span></td>
        </tr>
        <tr>
            <td class="label">Pengantaran</td>
            <td>: {{ ucfirst($pengantaran) }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Layanan</th>
                <th>Harga/kg</th>
                <th>Berat/Jml</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->nama_layanan }}</td>
                <td class="text-right">Rp {{ number_format($item->harga_per_kg, 0, ',', '.') }}</td>
                <td class="text-right">{{ $item->berat_kg }}</td>
                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if($ongkir > 0)
            <tr>
                <td colspan="3" class="text-right">Ongkos Kirim</td>
                <td class="text-right">Rp {{ number_format($ongkir, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" class="text-right">Total</td>
                <td class="text-right">Rp {{ number_format($total_harga, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Terima kasih telah menggunakan layanan BLaundry!</p>
        <p>&copy; {{ date('Y') }} BLaundry. All rights reserved.</p>
    </div>

</body>
</html>