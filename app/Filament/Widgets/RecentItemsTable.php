<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ItemResource;
use App\Models\Item;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentItemsTable extends TableWidget
{
	protected static ?int $sort = 14;
	protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

	public function table(Table $table): Table
	{
		return $table
			->query(Item::latest()->limit(5))
			->recordUrl(fn (Item $record): string => ItemResource::getUrl('edit', ['record' => $record]))
			->columns([
				TextColumn::make('name')
					->searchable()
					->sortable()
					->limit(25),
				TextColumn::make('price')
					->money('GHC')
					->sortable(),
				TextColumn::make('condition')
					->badge()
					->color(fn(string $state): string => match ($state) {
						'new' => 'success',
						'used' => 'warning',
					})
					->visibleFrom('md'),
				TextColumn::make('status')
					->badge()
					->color(fn(string $state): string => match ($state) {
						'active' => 'success',
						'pending_approval' => 'warning',
						'sold' => 'info',
						'rejected' => 'danger',
						'expired' => 'gray',
						default => 'gray',
					}),
			])
			->heading('Recent Items');
	}
}
