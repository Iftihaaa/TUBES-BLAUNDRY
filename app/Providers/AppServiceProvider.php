<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \Carbon\Carbon::setLocale('id');

        // forceScheme dihapus karena bikin CSS Filament tidak load di localhost

        FilamentView::registerRenderHook(
            'panels::head.end',
            fn () => Blade::render(
                '<script src="https://app.sandbox.midtrans.com/snap/snap.js" 
                    data-client-key="{{ config(\'midtrans.client_key\') }}"></script>'
            ),
        );
    }
}