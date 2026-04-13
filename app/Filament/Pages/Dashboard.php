<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
	public function getWidgets(): array
	{
		return [
			// \App\Filament\Widgets\WalletKpisOverview::class,
			\App\Filament\Widgets\EnhancedStatsOverview::class,
			\App\Filament\Widgets\RevenueStatsOverview::class,
			\App\Filament\Widgets\RevenueTrendChart::class,
			\App\Filament\Widgets\UserGrowthChart::class,
			\App\Filament\Widgets\ItemsByConditionChart::class,
			\App\Filament\Widgets\ItemsByCountryChart::class,
			// \App\Filament\Widgets\VerificationStatusChart::class,
			\App\Filament\Widgets\PackagePopularityChart::class,
			\App\Filament\Widgets\TopCategoriesChart::class,
			\App\Filament\Widgets\TopBrandsChart::class,
			\App\Filament\Widgets\ItemsOverview::class, // Platform Growth Overview
			\App\Filament\Widgets\PendingVerificationsTable::class,
			\App\Filament\Widgets\HighValueItemsTable::class,
			\App\Filament\Widgets\RecentItemsTable::class,
			\App\Filament\Widgets\TopSellerUsersTable::class,
			// Removed from dashboard home (still available in Wallet section/menu if needed):
			// \App\Filament\Widgets\WalletTransactionsTable::class,
		];
	}

	/**
	 * Responsive grid: 1 column on mobile, 2 on tablet, 4 on desktop.
	 */
	public function getColumns(): int|string|array
	{
		return [
			'default' => 1,
			'sm' => 2,
			'lg' => 4,
		];
	}
}
