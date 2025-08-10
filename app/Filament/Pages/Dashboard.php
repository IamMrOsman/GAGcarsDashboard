<?php

namespace App\Filament\Pages;

use App\Models\Item;
use App\Models\User;
use App\Models\WishList;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Promotion;
use App\Models\SpecialOffer;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Support\Facades\DB;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class Dashboard extends BaseDashboard
{
	public function getWidgets(): array
	{
		return [
			DashboardStatsOverview::class,
			ItemsByConditionChart::class,
			ItemsByStatusChart::class,
			TopCategoriesChart::class,
			TopBrandsChart::class,
			RecentItemsTable::class,
			TopSellerUsersTable::class,
			ActivePromotionsTable::class,
		];
	}

	public function getColumns(): int
	{
		// Return 4 columns for larger screens
		return 4;
	}
}

class DashboardStatsOverview extends StatsOverviewWidget
{
	protected function getStats(): array
	{
		return [
			Stat::make('Total Items', Item::count())
				->description('Total items in the marketplace')
				->descriptionIcon('heroicon-m-shopping-bag')
				->chart(
					Item::query()
						->select(DB::raw('count(*) as count'))
						->whereDate('created_at', '>', now()->subDays(7))
						->groupBy(DB::raw('Date(created_at)'))
						->pluck('count')
						->toArray()
				)
				->color('success'),

			Stat::make('Active Users', User::where('status', 1)->count())
				->description('Active users in the platform')
				->descriptionIcon('heroicon-m-users')
				->chart(
					User::query()
						->select(DB::raw('count(*) as count'))
						->whereDate('created_at', '>', now()->subDays(7))
						->groupBy(DB::raw('Date(created_at)'))
						->pluck('count')
						->toArray()
				)
				->color('primary'),

			Stat::make('Total Wishlists', WishList::count())
				->description('Items in wishlists')
				->descriptionIcon('heroicon-m-heart')
				->chart(
					WishList::query()
						->select(DB::raw('count(*) as count'))
						->whereDate('created_at', '>', now()->subDays(7))
						->groupBy(DB::raw('Date(created_at)'))
						->pluck('count')
						->toArray()
				)
				->color('danger'),

			Stat::make('Active Promotions', Promotion::where('status', 1)->count())
				->description('Current active promotions')
				->descriptionIcon('heroicon-m-megaphone')
				->chart(
					Promotion::query()
						->select(DB::raw('count(*) as count'))
						->whereDate('created_at', '>', now()->subDays(7))
						->groupBy(DB::raw('Date(created_at)'))
						->pluck('count')
						->toArray()
				)
				->color('warning'),
		];
	}
}

class ItemsByConditionChart extends ChartWidget
{
	protected static ?string $heading = 'Items by Condition';
	protected static ?int $sort = 2;

	protected function getData(): array
	{
		$data = Item::query()
			->selectRaw('condition, count(*) as count')
			->groupBy('condition')
			->get();

		return [
			'datasets' => [
				[
					'label' => 'Items',
					'data' => $data->pluck('count')->toArray(),
					'backgroundColor' => ['#36A2EB', '#FF6384'],
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

class ItemsByStatusChart extends ChartWidget
{
	protected static ?string $heading = 'Items by Status';
	protected static ?int $sort = 3;

	protected function getData(): array
	{
		$data = Item::query()
			->selectRaw('status, count(*) as count')
			->groupBy('status')
			->get();

		return [
			'datasets' => [
				[
					'label' => 'Items',
					'data' => $data->pluck('count')->toArray(),
					'backgroundColor' => ['#FF6384', '#36A2EB'],
				],
			],
			'labels' => $data->pluck('status')->map(fn($status) => $status ? 'Active' : 'Inactive')->toArray(),
		];
	}

	protected function getType(): string
	{
		return 'doughnut';
	}
}

class TopCategoriesChart extends ChartWidget
{
	protected static ?string $heading = 'Top Categories';
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
					'backgroundColor' => '#36A2EB',
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

class TopBrandsChart extends ChartWidget
{
	protected static ?string $heading = 'Top Brands';
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
					'backgroundColor' => '#FF6384',
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

class RecentItemsTable extends TableWidget
{
	protected static ?int $sort = 6;
	protected int|string|array $columnSpan = 2;

	public function table(Table $table): Table
	{
		return $table
			->query(Item::latest()->limit(5))
			->columns([
				TextColumn::make('name')
					->searchable()
					->sortable(),
				TextColumn::make('price')
					->money('GHC')
					->sortable(),
				TextColumn::make('condition')
					->badge()
					->color(fn(string $state): string => match ($state) {
						'new' => 'success',
						'used' => 'warning',
					}),
				IconColumn::make('status')
					->boolean(),
			])
			->heading('Recent Items');
	}
}

class TopSellerUsersTable extends TableWidget
{
	protected static ?int $sort = 7;
	protected int|string|array $columnSpan = 2;

	public function table(Table $table): Table
	{
		return $table
			->query(
				User::withCount('items')
					->orderByDesc('items_count')
					->limit(5)
			)
			->columns([
				TextColumn::make('name')
					->searchable()
					->sortable(),
				TextColumn::make('email')
					->searchable(),
				TextColumn::make('items_count')
					->label('Listed Items')
					->sortable(),
				IconColumn::make('paid_seller')
					->boolean(),
			])
			->heading('Top Sellers');
	}
}

class ActivePromotionsTable extends TableWidget
{
	protected static ?int $sort = 8;
	protected int|string|array $columnSpan = 'full';

	public function table(Table $table): Table
	{
		return $table
			->query(
				Promotion::where('status', 1)
					->latest()
					->limit(5)
			)
			->columns([
				TextColumn::make('title')
					->searchable()
					->sortable(),
				TextColumn::make('start_date')
					->date()
					->sortable(),
				TextColumn::make('end_date')
					->date()
					->sortable(),
				IconColumn::make('status')
					->boolean(),
			])
			->heading('Active Promotions');
	}
}
