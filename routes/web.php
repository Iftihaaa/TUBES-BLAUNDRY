<?php

use Illuminate\Support\Facades\Route;
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
