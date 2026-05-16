<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pencatatan Biaya</title>

    <style>
        body {
            font-family: sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
            font-size: 12px;
        }

        th {
            background: #dddddd;
        }
    </style>
</head>
<body>

<h2>Laporan Pencatatan Biaya</h2>

<p>
    Periode :
    {{ $periode }}
</p>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Pegawai</th>
            <th>Jenis Beban</th>
            <th>Nominal</th>
        </tr>
    </thead>

    <tbody>
        @foreach($biayas as $item)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $item->tanggal_catat }}</td>
            <td>{{ $item->pegawai->nama ?? '-' }}</td>
            <td>{{ $item->jenis_beban }}</td>
            <td>
                Rp {{ number_format($item->nominal,0,',','.') }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<h3>
    Total Pengeluaran :
    Rp {{ number_format($total,0,',','.') }}
</h3>

</body>
</html>