<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; }
        .header { background-color: #4794a9; color: white; padding: 20px; text-align: center; }
        .content { padding: 24px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .footer { text-align: center; font-size: 12px; color: #999; padding: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>BLaundry</h2>
        <p>Konfirmasi Pembayaran</p>
    </div>
    <div class="content">
        <p>Halo, <strong>{{ $data['customer_name'] }}</strong>!</p>
        <p>
            Pembayaran untuk pesanan <strong>{{ $data['kode_pemesanan'] }}</strong> 
            telah berhasil diterima.
        </p>
        <p>
            Status pembayaran: 
            <span class="badge badge-success">{{ $data['status_message'] }}</span>
        </p>
        <p>Invoice pembayaran terlampir di email ini. Pesanan kamu sedang kami proses ya!</p>
        <p>Terima kasih telah mempercayakan laundry kamu ke <strong>BLaundry</strong> 🙏</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} BLaundry. All rights reserved.</p>
    </div>
</body>
</html>