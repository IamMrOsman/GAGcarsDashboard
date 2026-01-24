<?php

namespace App\Filament\Widgets;

use App\Models\Brand;
use Filament\Widgets\ChartWidget;

class TopBrandsChart extends ChartWidget
{
	protected static ?string $heading = 'Top Brands by Items';
	protected static ?int $sort = 5;

	protected function getData(): array
	{
		$data = Brand::withCount('items')
			->orderByDesc('items_count')
			->limit(5)
			->get();

		return [
			'datasets' => [
				[
					'label' => 'Items',
					'data' => $data->pluck('items_count')->toArray(),
					'backgroundColor' => '#ec4899',
				],
			],
			'labels' => $data->pluck('name')->toArray(),
		];
	}

	protected function getType(): string
	{
		return 'bar';
	}
}
