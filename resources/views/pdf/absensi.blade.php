<!DOCTYPE html>
<html>
<head>
    <title>Rekap Absensi</title>

    <style>
        body {
            font-family: sans-serif;
        }

        h2 {
            text-align: center;
            margin-bottom: 5px;
        }

        p {
            text-align: center;
            margin-top: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid black;
        }

        th {
            background-color: #f2f2f2;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }
    </style>
</head>
<body>

    <h2>Rekap Absensi Pegawai</h2>

    <p>
        Bulan:
        {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Pegawai</th>
                <th>Kehadiran</th>
                <th>Tanggal</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($absensis as $absensi)
                <tr>
                    <td>{{ $absensi->id_absen }}</td>
                    <td>{{ $absensi->pegawai->nama }}</td>
                    <td>{{ $absensi->kehadiran }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($absensi->created_at)->format('d-m-Y') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>