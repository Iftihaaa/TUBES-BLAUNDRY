<!DOCTYPE html>
<html>
<head>
    <title>Slip Gaji</title>
</head>
<body>

    <h2>Slip Gaji Pegawai</h2>

    <hr>

    <p>Nama Pegawai : {{ $penggajian->pegawai->nama }}</p>

    <p>Jumlah Hadir : {{ $penggajian->jumlah_hadir }}</p>

    <p>Jumlah Tidak Hadir : {{ $penggajian->jumlah_tidak_hadir }}</p>

    <p>Gaji Pokok : Rp {{ number_format($penggajian->gaji_pokok) }}</p>

    <p>Bonus : Rp {{ number_format($penggajian->nominal_bonus) }}</p>

    <p>Total Gaji : Rp {{ number_format($penggajian->total_gaji) }}</p>

</body>
</html>