<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Status Pengiriman Email</title>
</head>
<body>
    <h1>Status Pengiriman Email</h1>
    <p>{{ $message }}</p>
    <p>Waktu: {{ $timestamp }}</p>
    <p><a href="{{ url('/') }}">Kembali ke beranda</a></p>
</body>
</html>
