<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
	protected static string $resource = TransactionResource::class;

	protected function getHeaderActions(): array
	{
		return [
			// Actions\CreateAction::make(),
		];
	}

	public function getTabs(): array
	{
		return [
			'all' => Tab::make('All')
				->badge(TransactionResource::getEloquentQuery()->count()),
			'successful' => Tab::make('Successful')
				->badge(TransactionResource::getEloquentQuery()->where('status', 'success')->count())
				->modifyQueryUsing(fn ($query) => $query->where('status', 'success')),
			'failed' => Tab::make('Failed')
				->badge(TransactionResource::getEloquentQuery()->where('status', 'failed')->count())
				->modifyQueryUsing(fn ($query) => $query->where('status', 'failed')),
		];
	}
}
