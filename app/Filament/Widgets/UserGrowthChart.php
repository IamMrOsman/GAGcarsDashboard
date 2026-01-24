<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Verification;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserGrowthChart extends ChartWidget
{
	protected static ?string $heading = 'User Growth (Last 30 Days)';
	protected static ?int $sort = 4;
	protected int|string|array $columnSpan = 2;

	protected function getData(): array
	{
		$dates = collect();
		for ($i = 29; $i >= 0; $i--) {
			$dates->push(now()->subDays($i)->format('Y-m-d'));
		}

		$newUsers = User::query()
			->select(
				DB::raw('DATE(created_at) as date'),
				DB::raw('COUNT(*) as count')
			)
			->whereDate('created_at', '>', now()->subDays(30))
			->groupBy('date')
			->pluck('count', 'date');

		$verifiedUsers = Verification::query()
			->where('status', 'verified')
			->select(
				DB::raw('DATE(updated_at) as date'),
				DB::raw('COUNT(*) as count')
			)
			->whereDate('updated_at', '>', now()->subDays(30))
			->groupBy('date')
			->pluck('count', 'date');

		return [
			'datasets' => [
				[
					'label' => 'New Users',
					'data' => $dates->map(fn($date) => $newUsers[$date] ?? 0)->toArray(),
					'borderColor' => '#3b82f6',
					'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
				],
				[
					'label' => 'Verified Users',
					'data' => $dates->map(fn($date) => $verifiedUsers[$date] ?? 0)->toArray(),
					'borderColor' => '#10b981',
					'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
				],
			],
			'labels' => $dates->map(fn($date) => \Carbon\Carbon::parse($date)->format('M d'))->toArray(),
		];
	}

	protected function getType(): string
	{
		return 'line';
	}
}
