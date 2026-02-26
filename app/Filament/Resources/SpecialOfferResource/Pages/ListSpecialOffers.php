<?php

namespace App\Filament\Resources\SpecialOfferResource\Pages;

use App\Filament\Resources\SpecialOfferResource;
use App\Models\SpecialOffer;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSpecialOffers extends ListRecords
{
	protected static string $resource = SpecialOfferResource::class;

	protected function getHeaderActions(): array
	{
		return [
			Actions\CreateAction::make(),
		];
	}

	public function getTabs(): array
	{
		return [
			'running' => Tab::make('Running')
				->icon('heroicon-m-play-circle')
				->modifyQueryUsing(fn (Builder $query) => $query
					->where(function (Builder $q) {
						$q->whereNull('start_at')->orWhere('start_at', '<=', now());
					})
					->where(function (Builder $q) {
						$q->whereNull('end_at')->orWhere('end_at', '>=', now());
					}))
				->badge(SpecialOffer::query()
					->where(function (Builder $q) {
						$q->whereNull('start_at')->orWhere('start_at', '<=', now());
					})
					->where(function (Builder $q) {
						$q->whereNull('end_at')->orWhere('end_at', '>=', now());
					})
					->count()),
			'expired' => Tab::make('Expired')
				->icon('heroicon-m-stop-circle')
				->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('end_at')->where('end_at', '<', now()))
				->badge(SpecialOffer::query()->whereNotNull('end_at')->where('end_at', '<', now())->count()),
		];
	}
}
