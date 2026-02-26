<?php

namespace App\Filament\Resources\PromotionResource\Pages;

use App\Filament\Resources\PromotionResource;
use App\Models\Promotion;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPromotions extends ListRecords
{
	protected static string $resource = PromotionResource::class;

	protected function getHeaderActions(): array
	{
		return [
			Actions\CreateAction::make(),
		];
	}

	public function getTabs(): array
	{
		return [
			'all' => Tab::make('All')
				->icon('heroicon-m-squares-2x2')
				->badge(Promotion::count()),
			'active' => Tab::make('Active')
				->icon('heroicon-m-play-circle')
				->modifyQueryUsing(fn (Builder $query) => $query->where(function (Builder $q) {
					$q->whereNull('end_at')->orWhere('end_at', '>=', now());
				}))
				->badge(Promotion::query()->where(function (Builder $q) {
					$q->whereNull('end_at')->orWhere('end_at', '>=', now());
				})->count()),
			'expired' => Tab::make('Expired')
				->icon('heroicon-m-stop-circle')
				->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('end_at')->where('end_at', '<', now()))
				->badge(Promotion::query()->whereNotNull('end_at')->where('end_at', '<', now())->count()),
		];
	}
}
