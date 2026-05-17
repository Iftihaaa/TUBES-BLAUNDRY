<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Mail\LaporanBeban;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\PengirimanEmailController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\EmailPemesananController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-login', function () {
    $user = User::first();
    if ($user) {
        Auth::login($user);
        return redirect('/admin');
    }
    return 'No user found. Run: php artisan db:seed';
});

Route::post('/midtrans/snap-token', [MidtransController::class, 'getSnapToken'])
    ->name('midtrans.snap-token');

Route::get('/midtrans/cek-status', [MidtransController::class, 'cekStatus'])
    ->name('midtrans.cek-status');

// Tes kirim email laporan beban
Route::get('/proses_kirim_email_laporan_beban', [PengirimanEmailController::class, 'proses_kirim_email_laporan_beban'])
    ->name('proses_kirim_email_laporan_beban');

Route::get('/email-pemesanan/proses', [EmailPemesananController::class, 'proses_semua']);