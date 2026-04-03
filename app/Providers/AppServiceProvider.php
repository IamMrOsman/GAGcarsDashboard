<?php

namespace App\Providers;

use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentIcon;
use Jeffgreco13\FilamentBreezy\Livewire\BrowserSessions;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;
use Jeffgreco13\FilamentBreezy\Livewire\SanctumTokens;
use Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication;
use Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword;
use Livewire\Livewire;
use App\Filament\Widgets\ItemSettingsWidget;
use App\Models\Post;
use App\Models\Broadcast;
use App\Observers\PostObserver;
use App\Observers\BroadcastObserver;
use App\Services\PusherSettingsService;

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
        // Register Filament Breezy profile components early so they exist for
        // Livewire POST /livewire/update (panel may not be booted on that request).
        Livewire::component('personal_info', PersonalInfo::class);
        Livewire::component('update_password', UpdatePassword::class);
        Livewire::component('two_factor_authentication', TwoFactorAuthentication::class);
        Livewire::component('sanctum_tokens', SanctumTokens::class);
        Livewire::component('browser_sessions', BrowserSessions::class);

        // Register Filament header widgets so they resolve on Livewire update requests.
        Livewire::component('app.filament.widgets.item-settings-widget', ItemSettingsWidget::class);

        FilamentIcon::register([
            'panels::sidebar.collapse-button' => 'heroicon-o-bars-2',
            'panels::sidebar.expand-button' => 'heroicon-o-bars-2',
            'white' => Color::hex('#ffff'),
        ]);

		Post::observe(PostObserver::class);
		Broadcast::observe(BroadcastObserver::class);

		try {
			PusherSettingsService::applyToConfig();
		} catch (\Throwable $e) {
			// e.g. migrate before settings table exists
		}

		Gate::define('viewApiDocs', function () {
			return true;
		});

		Gate::before(function ($user, $ability) {
			return true;
		});
    }
}
