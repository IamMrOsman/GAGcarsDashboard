<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;

class TopCategoriesChart extends ChartWidget
{
	protected static ?string $heading = 'Top Categories by Items';
	protected static ?int $sort = 4;

	protected function getData(): array
	{
		$data = Category::withCount('items')
			->orderByDesc('items_count')
			->limit(5)
			->get();

		return [
			'datasets' => [
				[
					'label' => 'Items',
					'data' => $data->pluck('items_count')->toArray(),
					'backgroundColor' => '#3b82f6',
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
