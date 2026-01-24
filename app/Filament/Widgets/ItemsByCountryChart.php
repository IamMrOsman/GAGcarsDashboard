<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ItemsByCountryChart extends ChartWidget
{
	protected static ?string $heading = 'Items by Country';
	protected static ?int $sort = 6;

	protected function getData(): array
	{
		$data = Item::query()
			->join('countries', 'items.country_id', '=', 'countries.id')
			->select('countries.name', DB::raw('count(items.id) as count'))
			->groupBy('countries.name')
			->orderByDesc('count')
			->limit(5)
			->get();

		return [
			'datasets' => [
				[
					'label' => 'Items',
					'data' => $data->pluck('count')->toArray(),
					'backgroundColor' => ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'],
				],
			],
			'labels' => $data->pluck('name')->toArray(),
		];
	}

	protected function getType(): string
	{
		return 'doughnut';
	}
}
