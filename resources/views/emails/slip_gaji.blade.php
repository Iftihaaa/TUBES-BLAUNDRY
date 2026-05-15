@php
    use Carbon\Carbon;

    $pegawai = $penggajian->pegawai;
@endphp

<h1>Slip Gaji</h1>

<p>ID Penggajian: {{ $penggajian->id_penggajian }}</p>

<p>Nama Pegawai: {{ $pegawai?->nama ?? 'N/A' }}</p>

<p>
    Tanggal Bayar:
    {{ Carbon::parse($penggajian->tanggal_bayar)->format('d-m-Y') }}
</p>

<p>Jumlah Hadir: {{ $penggajian->jumlah_hadir }}</p>

<p>Jumlah Tidak Hadir: {{ $penggajian->jumlah_tidak_hadir }}</p>

<p>
    Gaji Pokok:
    Rp {{ number_format($penggajian->gaji_pokok, 0, ',', '.') }}
</p>

<p>
    Bonus:
    Rp {{ number_format($penggajian->nominal_bonus ?? 0, 0, ',', '.') }}
</p>

<p>
    Total Gaji:
    Rp {{ number_format($penggajian->total_gaji, 0, ',', '.') }}
</p>

<p>
    Status Pembayaran:
    {{ $penggajian->status_pembayaran ?? 'Sudah Dibayar' }}
</p>