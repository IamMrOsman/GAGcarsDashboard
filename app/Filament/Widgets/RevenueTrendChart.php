<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueTrendChart extends ChartWidget
{
	protected static ?string $heading = 'Revenue Trend (Last 30 Days)';
	protected static ?int $sort = 7;
	protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

	protected function getData(): array
	{
		$data = Transaction::query()
			->select(
				DB::raw('DATE(created_at) as date'),
				DB::raw('SUM(amount) as total')
			)
			->whereDate('created_at', '>', now()->subDays(30))
			->groupBy('date')
			->orderBy('date')
			->get();

		return [
			'datasets' => [
				[
					'label' => 'Revenue (GHC)',
					'data' => $data->pluck('total')->toArray(),
					'borderColor' => '#10b981',
					'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
					'fill' => true,
				],
			],
			'labels' => $data->pluck('date')->map(fn($date) => \Carbon\Carbon::parse($date)->format('M d'))->toArray(),
		];
	}

	protected function getType(): string
	{
		return 'line';
	}
}
