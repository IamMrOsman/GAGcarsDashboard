<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\Category;
use App\Models\Brand;
use Filament\Widgets\ChartWidget;

class ItemsOverview extends ChartWidget
{
	protected static ?int $sort = 3;
	protected int|string|array $columnSpan = 'full';
	protected static ?string $heading = 'Items Overview';

	protected function getData(): array
	{
		$categories = Category::withCount('items')
			->orderByDesc('items_count')
			->limit(5)
			->get();

		$brands = Brand::withCount('items')
			->orderByDesc('items_count')
			->limit(5)
			->get();

		return [
			'datasets' => [
				[
					'label' => 'By Category',
					'data' => $categories->pluck('items_count')->toArray(),
					'backgroundColor' => '#36A2EB',
				],
				[
					'label' => 'By Brand',
					'data' => $brands->pluck('items_count')->toArray(),
					'backgroundColor' => '#FF6384',
				],
			],
			'labels' => $categories->pluck('name')->toArray(),
		];
	}

	protected function getType(): string
	{
		return 'bar';
	}
}
