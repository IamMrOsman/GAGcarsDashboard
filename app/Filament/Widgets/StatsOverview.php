<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\User;
use App\Models\WishList;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class StatsOverview extends BaseWidget
{
	protected static ?int $sort = 2;
	protected int|string|array $columnSpan = 'full';
	protected function getStats(): array
	{
		return [
			Stat::make('Total Items', Item::count())
				->description('Total items in the marketplace')
				->descriptionIcon('tabler-car-suv')
				->chart(
					Trend::model(Item::class)
						->between(
							start: now()->subDays(30),
							end: now(),
						)
						->perDay()
						->count()
						->pluck('aggregate')->toArray()
				)
				->color('success'),

			Stat::make('Verified Users', User::whereNotNull('email_verified_at')->count())
				->description('Email verified users')
				->descriptionIcon('heroicon-o-users')
				->chart(
					Trend::query(
						User::query()->whereNotNull('email_verified_at')
					)
						->between(
							start: now()->subDays(30),
							end: now(),
						)
						->perDay()
						->count()
						->pluck('aggregate')->toArray()
				)
				->color('primary'),

			Stat::make('Total Wishlists', WishList::count())
				->description('Items in wishlists')
				->descriptionIcon('heroicon-o-heart')
				->chart(
					Trend::model(WishList::class)
						->between(
							start: now()->subDays(30),
							end: now(),
						)
						->perDay()
						->count()
						->pluck('aggregate')->toArray()
				)
				->color('danger'),
		];
	}
}
