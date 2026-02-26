<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\User;
use App\Models\WishList;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EnhancedStatsOverview extends StatsOverviewWidget
{
	protected function getStats(): array
	{
		// Calculate current period vs previous period
		$currentPeriodStart = now()->startOfMonth();
		$previousPeriodStart = now()->subMonth()->startOfMonth();
		$previousPeriodEnd = now()->subMonth()->endOfMonth();

		// Items
		$totalItems = Item::count();
		$currentMonthItems = Item::whereBetween('created_at', [$currentPeriodStart, now()])->count();
		$previousMonthItems = Item::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();
		$itemsChange = $previousMonthItems > 0
			? round((($currentMonthItems - $previousMonthItems) / $previousMonthItems) * 100, 1)
			: 0;

		// Users (total count; description/chart show new signup growth)
		$totalUsers = User::count();
		$currentMonthUsers = User::whereBetween('created_at', [$currentPeriodStart, now()])->count();
		$previousMonthUsers = User::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();
		$usersChange = $previousMonthUsers > 0
			? round((($currentMonthUsers - $previousMonthUsers) / $previousMonthUsers) * 100, 1)
			: 0;

		// Items Sold
		$itemsSold = Item::where('status', 'sold')->count();
		$currentMonthSold = Item::where('status', 'sold')
			->whereBetween('updated_at', [$currentPeriodStart, now()])
			->count();

		// Wishlists
		$totalWishlists = WishList::count();

		return [
			Stat::make('Total Items', number_format($totalItems))
				->description($itemsChange >= 0 ? "+{$itemsChange}% from last month" : "{$itemsChange}% from last month")
				->descriptionIcon($itemsChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
				->chart(
					Item::query()
						->select(DB::raw('count(*) as count'))
						->whereDate('created_at', '>', now()->subDays(7))
						->groupBy(DB::raw('Date(created_at)'))
						->pluck('count')
						->toArray()
				)
				->color($itemsChange >= 0 ? 'success' : 'danger'),

			Stat::make('Total Users', number_format($totalUsers))
				->description($usersChange >= 0 ? "+{$usersChange}% new this month" : "{$usersChange}% this month")
				->descriptionIcon($usersChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
				->chart(
					User::query()
						->select(DB::raw('count(*) as count'))
						->whereDate('created_at', '>', now()->subDays(7))
						->groupBy(DB::raw('Date(created_at)'))
						->pluck('count')
						->toArray()
				)
				->color($usersChange >= 0 ? 'success' : 'danger'),

			Stat::make('Items Sold', number_format($itemsSold))
				->description($currentMonthSold . ' sold this month')
				->descriptionIcon('heroicon-m-check-circle')
				->color('success'),

			Stat::make('Total Wishlists', number_format($totalWishlists))
				->description('User saved items')
				->descriptionIcon('heroicon-m-heart')
				->chart(
					WishList::query()
						->select(DB::raw('count(*) as count'))
						->whereDate('created_at', '>', now()->subDays(7))
						->groupBy(DB::raw('Date(created_at)'))
						->pluck('count')
						->toArray()
				)
				->color('danger'),
		];
	}
}
