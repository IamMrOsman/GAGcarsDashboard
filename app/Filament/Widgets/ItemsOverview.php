<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\User;
use App\Models\Brand;
use App\Models\WishList;
use App\Models\Promotion;
use App\Models\BrandModel;
use App\Models\SpecialOffer;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class ItemsOverview extends ChartWidget
{
	protected static ?int $sort = 6;
	protected int|string|array $columnSpan = 'full';
	protected static ?string $heading = 'Platform Growth Overview';
	protected static ?string $maxHeight = '400px';

	protected function getOptions(): array
	{
		return [
			'scales' => [
				'x' => [
					'display' => false,
				],
			],
			'plugins' => [
				'tooltip' => [
					'enabled' => true,
				],
			],
		];
	}

	protected function getData(): array
	{
		$end = now();
		$start = now()->subDays(30);

		$items = Trend::model(Item::class)
			->between(start: $start, end: $end)
			->perDay()
			->count();

		$users = Trend::model(User::class)
			->between(start: $start, end: $end)
			->perDay()
			->count();

		$makes = Trend::model(Brand::class)
			->between(start: $start, end: $end)
			->perDay()
			->count();

		$models = Trend::model(BrandModel::class)
			->between(start: $start, end: $end)
			->perDay()
			->count();

		$wishlist = Trend::model(WishList::class)
			->between(start: $start, end: $end)
			->perDay()
			->count();

		$promotions = Trend::model(Promotion::class)
			->between(start: $start, end: $end)
			->perDay()
			->count();

		$specialOffers = Trend::model(SpecialOffer::class)
			->between(start: $start, end: $end)
			->perDay()
			->count();

		return [
			'datasets' => [
				[
					'label' => 'Items',
					'data' => $items->pluck('aggregate')->toArray(),
					'borderColor' => '#36A2EB',
					'fill' => false,
					'tension' => 0.3,
				],
				[
					'label' => 'Users',
					'data' => $users->pluck('aggregate')->toArray(),
					'borderColor' => '#FF6384',
					'fill' => false,
					'tension' => 0.3,
				],
				[
					'label' => 'Makes',
					'data' => $makes->pluck('aggregate')->toArray(),
					'borderColor' => '#4BC0C0',
					'fill' => false,
					'tension' => 0.3,
				],
				[
					'label' => 'Models',
					'data' => $models->pluck('aggregate')->toArray(),
					'borderColor' => '#9966FF',
					'fill' => false,
					'tension' => 0.3,
				],
				[
					'label' => 'Wishlist Items',
					'data' => $wishlist->pluck('aggregate')->toArray(),
					'borderColor' => '#FF9F40',
					'fill' => false,
					'tension' => 0.3,
				],
				[
					'label' => 'Promotions',
					'data' => $promotions->pluck('aggregate')->toArray(),
					'borderColor' => '#FFCD56',
					'fill' => false,
					'tension' => 0.3,
				],
				[
					'label' => 'Special Offers',
					'data' => $specialOffers->pluck('aggregate')->toArray(),
					'borderColor' => '#C9CBCF',
					'fill' => false,
					'tension' => 0.3,
				],
			],
			'labels' => $items->pluck('date')->toArray(),
		];
	}

	protected function getType(): string
	{
		return 'line';
	}
}
