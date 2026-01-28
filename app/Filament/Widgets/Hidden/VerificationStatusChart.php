<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;

class VerificationStatusChart extends ChartWidget
{
	protected static ?string $heading = 'User Verification Status';
	protected static ?int $sort = 7;

	protected function getData(): array
	{
		$verified = User::whereHas('verifications', function($query) {
			$query->where('status', 'verified');
		})->count();

		$pending = User::whereHas('verifications', function($query) {
			$query->where('status', 'pending');
		})->count();

		$unverified = User::whereDoesntHave('verifications')->count();

		return [
			'datasets' => [
				[
					'label' => 'Users',
					'data' => [$verified, $pending, $unverified],
					'backgroundColor' => ['#10b981', '#f59e0b', '#ef4444'],
				],
			],
			'labels' => ['Verified', 'Pending', 'Unverified'],
		];
	}

	protected function getType(): string
	{
		return 'doughnut';
	}
}
