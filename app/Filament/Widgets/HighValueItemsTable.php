<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ItemResource;
use App\Models\Item;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class HighValueItemsTable extends TableWidget
{
	protected static ?int $sort = 13;
	protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

	public function table(Table $table): Table
	{
		return $table
			->query(Item::where('status', 'active')->orderByDesc('price')->limit(5))
			->recordUrl(fn (Item $record): string => ItemResource::getUrl('edit', ['record' => $record]))
			->columns([
				TextColumn::make('name')
					->searchable()
					->sortable()
					->limit(30),
				TextColumn::make('price')
					->money('GHC')
					->sortable(),
				TextColumn::make('category.name')
					->label('Category')
					->badge()
					->color('success'),
			])
			->heading('High-Value Items');
	}
}
