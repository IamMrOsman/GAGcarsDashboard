<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Verification;
use App\Models\Promotion;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RevenueStatsOverview extends StatsOverviewWidget
{
	protected function getStats(): array
	{
		$totalRevenue = Transaction::where('status', 'success')->sum('amount') ?? 0;
		$currentMonthRevenue = Transaction::whereBetween('created_at', [now()->startOfMonth(), now()])->sum('amount') ?? 0;
		$previousMonthRevenue = Transaction::whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->sum('amount') ?? 0;

		$revenueChange = $previousMonthRevenue > 0
			? round((($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 1)
			: 0;

		$verifiedUsers = User::whereHas('verifications', function($query) {
			$query->where('status', 'verified');
		})->count();

		$totalUsers = User::count();
		$verificationRate = $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100, 1) : 0;

		$pendingVerifications = Verification::where('status', 'pending')->count();

		$activePromotions = Promotion::where('status', '1')
			->where(function ($query) {
				$query->whereNull('start_at')->orWhere('start_at', '<=', now());
			})
			->where(function ($query) {
				$query->whereNull('end_at')->orWhere('end_at', '>=', now());
			})
			->where('status', 'active')
			->count();

		return [
			Stat::make('Total Revenue', 'GHC ' . number_format($totalRevenue, 2))
				->description($revenueChange >= 0 ? "+{$revenueChange}% from last month" : "{$revenueChange}% from last month")
				->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
				->chart(
					Transaction::query()
						->where('status', 'success')
						->select(DB::raw('SUM(amount) as total'))
						->whereDate('created_at', '>', now()->subDays(7))
						->groupBy(DB::raw('Date(created_at)'))
						->pluck('total')
						->toArray()
				)
				->color($revenueChange >= 0 ? 'success' : 'warning'),

			Stat::make('Verified Users', number_format($verifiedUsers))
				->description("{$verificationRate}% verification rate")
				->descriptionIcon('heroicon-m-shield-check')
				->color('info'),

			Stat::make('Pending Verifications', number_format($pendingVerifications))
				->description('Awaiting review')
				->descriptionIcon('heroicon-m-clock')
				->color($pendingVerifications > 10 ? 'warning' : 'gray'),

			Stat::make('Active Promotions', number_format($activePromotions))
				->description('Currently running')
				->descriptionIcon('heroicon-m-megaphone')
				->color('warning'),
		];
	}
}
