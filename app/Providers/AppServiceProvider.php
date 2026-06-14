<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //ini seharusnya kosong, kalau dipanggil dari ngrok maka seperti ini
        if (config('app.env') === 'local' && str_contains(request()->getHost(), 'ngrok')) {
            \URL::forceScheme('https');
        }
    }
}