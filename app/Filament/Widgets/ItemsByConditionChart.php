<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Widgets\ChartWidget;

class ItemsByConditionChart extends ChartWidget
{
	protected static ?string $heading = 'Items by Condition';
	protected static ?int $sort = 5;

	protected function getData(): array
	{
		$data = Item::query()
			->select('condition')
			->selectRaw('count(*) as count')
			->groupBy('condition')
			->get();

		return [
			'datasets' => [
				[
					'label' => 'Items',
					'data' => $data->pluck('count')->toArray(),
					'backgroundColor' => ['#10b981', '#f59e0b'],
				],
			],
			'labels' => $data->pluck('condition')->map(fn($condition) => ucfirst($condition))->toArray(),
		];
	}

	protected function getType(): string
	{
		return 'pie';
	}
}
