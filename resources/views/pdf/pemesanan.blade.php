<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Pemesanan B Laundry</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; } /* 👈 Tambahkan ini */
    </style>
</head>
<body>
    <h2>Daftar Pemesanan B Laundry</h2>
    <table>
        <thead>
            <tr>
                <th>No Pesanan</th>
                <th>Nama Pembeli</th>
                <th>Status</th>
                <th>Tagihan</th>
                <th>Tgl</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pemesanan as $p)
            <tr>
                <td>{{ $p->kode_pemesanan }}</td>
                <td>{{ optional($p->member)->nama_pelanggan }}</td>
                <td>{{ $p->status }}</td>
                <td class="text-right">{{ 'Rp ' . number_format((float) $p->total_harga, 0, ',', '.') }}</td>
                <td>{{ $p->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
