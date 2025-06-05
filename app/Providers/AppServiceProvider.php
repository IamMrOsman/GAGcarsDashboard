<?php

namespace App\Providers;

use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentIcon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentIcon::register([
            'panels::sidebar.collapse-button' => 'heroicon-o-bars-2',
            'panels::sidebar.expand-button' => 'heroicon-o-bars-2',
            'white' => Color::hex('#ffff'),
        ]);

		Gate::define('viewApiDocs', function () {
			return true;
		});
    }
}
