<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MidtransController;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

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

// ================================================================
// TAMBAHKAN KE routes/web.php
// ================================================================

// Midtrans - generate snap token (butuh CSRF, dipanggil dari Filament admin)
Route::post('/midtrans/snap-token', [App\Http\Controllers\MidtransController::class, 'getSnapToken'])
    ->name('midtrans.snap-token');

// Cek status pembayaran manual (opsional, bisa diakses dari browser admin)
Route::get('/midtrans/cek-status', [App\Http\Controllers\MidtransController::class, 'cekStatus'])
    ->name('midtrans.cek-status');