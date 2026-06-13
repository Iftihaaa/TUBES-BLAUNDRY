<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; }
        .header { background-color: #4794a9; color: white; padding: 20px; text-align: center; }
        .content { padding: 24px; }
        .footer { text-align: center; font-size: 12px; color: #999; padding: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>BLaundry</h2>
        <p>Pesanan Selesai!</p>
    </div>
    <div class="content">
        <p>Halo, <strong>{{ $data['customer_name'] }}</strong>!</p>
        <p>
            Kabar baik! Pesanan laundry kamu dengan kode 
            <strong>{{ $data['kode_pemesanan'] }}</strong> sudah selesai diproses.
        </p>

        @if($data['pengantaran'] === 'ambil sendiri')
            <p>Pesanan kamu sudah siap untuk <strong>diambil</strong> di tempat kami.</p>
        @elseif($data['pengantaran'] === 'antar saja')
            <p>Pesanan kamu sedang dalam perjalanan untuk <strong>diantar</strong> ke alamat kamu.</p>
        @elseif($data['pengantaran'] === 'antar-jemput')
            <p>Pesanan kamu sedang dalam perjalanan untuk <strong>diantar</strong> ke alamat kamu.</p>
        @endif

        <p>Terima kasih telah menggunakan layanan <strong>BLaundry</strong> 🙏</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} BLaundry. All rights reserved.</p>
    </div>
</body>
</html>