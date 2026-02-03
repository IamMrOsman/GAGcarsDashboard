<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Rupadana\ApiService\ApiServicePlugin;
use Filament\Http\Middleware\Authenticate;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Jeffgreco13\FilamentBreezy\Middleware\MustTwoFactor;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use SolutionForest\FilamentSimpleLightBox\SimpleLightBoxPlugin;

class WatchtowerPanelProvider extends PanelProvider
{
	public function panel(Panel $panel): Panel
	{
		return $panel
			->default()
			->id('watchtower')
			->path('watchtower')
			->login()
			->colors([
				'primary' => Color::Red,
			])
			->brandLogo(asset('img/gag-logo.png'))
			->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
			->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
			->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
			->pages([
				Pages\Dashboard::class,
			])
			->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
			->widgets([
				// \App\Filament\Widgets\AccountWidget::class,
				// Widgets\FilamentInfoWidget::class,
				// \App\Filament\Widgets\StatsOverview::class,
				// \App\Filament\Widgets\ItemsOverview::class,
				// \App\Filament\Widgets\LatestItems::class,
			])
			->middleware([
				EncryptCookies::class,
				AddQueuedCookiesToResponse::class,
				StartSession::class,
				AuthenticateSession::class,
				ShareErrorsFromSession::class,
				VerifyCsrfToken::class,
				SubstituteBindings::class,
				DisableBladeIconComponents::class,
				DispatchServingFilamentEvent::class,
			])
			->spa()
			->plugins([
			    FilamentShieldPlugin::make(),
				ApiServicePlugin::make(),
				SimpleLightBoxPlugin::make(),
				BreezyCore::make()
					->myProfile(
						shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
						userMenuLabel: 'My Profile', // Customizes the 'account' link label in the panel User Menu (default = null)
						shouldRegisterNavigation: false, // Adds a main navigation item for the My Profile page (default = false)
						navigationGroup: 'Settings', // Sets the navigation group for the My Profile page (default = null)
						hasAvatars: false, // Enables the avatar upload form component (default = false)
						slug: 'my-profile'
					)
					->enableTwoFactorAuthentication(
						force: false, // force the user to enable 2FA before they can use the application (default = false)
						authMiddleware: MustTwoFactor::class // optionally, customize 2FA auth middleware or disable it to register manually by setting false
					)
					->enableBrowserSessions(condition: true)
			])
			->databaseNotifications()
			->maxContentWidth('full')
			->unsavedChangesAlerts()
			->sidebarWidth('17rem')
			->sidebarCollapsibleOnDesktop()
			->authMiddleware([
				Authenticate::class,
			]);
	}
}
