<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pencatatan Biaya</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .report-box {
            width: 100%;
            padding: 20px;
            border: 1px solid #eee;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        table th {
            background: #f2f2f2;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
        }
        .info {
            margin-top: 10px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="report-box">
        <div class="title">LAPORAN PENCATATAN BIAYA</div>

        <div class="info">
            <strong>Periode:</strong> {{ $periode ?? 'Semua' }}<br>
            <strong>Tanggal Cetak:</strong> {{ date('d-m-Y') }}
        </div>

        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis Beban</th>
                    <th>Pegawai</th>
                    <th>COA</th>
                    <th>Nominal</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($biayas as $biaya)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($biaya->tanggal_catat)->format('d-m-Y') }}</td>
                    <td>{{ $biaya->jenis_beban }}</td>
                    <td>{{ $biaya->pegawai->nama_pegawai ?? 'N/A' }}</td>
                    <td>{{ $biaya->coa->nama_akun ?? 'N/A' }}</td>
                    <td class="text-right">{{ rupiah($biaya->nominal, 0, ',', '.') }}</td>
                    <td>{{ $biaya->keterangan ?? '-' }}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="4" class="text-right"><strong>Total</strong></td>
                    <td class="text-right"><strong>{{ rupiah($total ?? collect($biayas)->sum('nominal'), 0, ',', '.') }}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <p style="margin-top: 30px;">Laporan ini dibuat secara otomatis oleh sistem.</p>
    </div>
</body>
</html>