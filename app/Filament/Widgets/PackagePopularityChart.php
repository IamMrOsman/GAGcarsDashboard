<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PackagePopularityChart extends ChartWidget
{
	protected static ?string $heading = 'Most Popular Packages';
	protected static ?int $sort = 8;
	protected int|string|array $columnSpan = 2;

	protected function getData(): array
	{
		$data = Transaction::query()
			->join('packages', 'transactions.package_id', '=', 'packages.id')
			->select('packages.name', DB::raw('COUNT(*) as count'))
			->groupBy('packages.name')
			->orderByDesc('count')
			->limit(5)
			->get();

		return [
			'datasets' => [
				[
					'label' => 'Purchases',
					'data' => $data->pluck('count')->toArray(),
					'backgroundColor' => '#8b5cf6',
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
