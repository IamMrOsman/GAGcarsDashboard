<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestItems extends BaseWidget
{
	protected static ?int $sort = 4;
	protected int|string|array $columnSpan = 'full';

	public function table(Table $table): Table
	{
		return $table
			->query(Item::latest()->limit(5))
			->columns([
				TextColumn::make('name')
					->searchable()
					->sortable(),
				TextColumn::make('category.name')
					->searchable()
					->sortable(),
				TextColumn::make('brand.name')
					->label('Brand')
					->searchable()
					->sortable(),
				TextColumn::make('price')
					->money('GHC')
					->sortable(),
				TextColumn::make('condition')
					->badge()
					->color(fn(string $state): string => match (strtolower($state)) {
						'new', 'brandnew' => 'success',
						'used' => 'warning',
						default => 'gray',
					}),
				IconColumn::make('status')
					->boolean(),
			])
			->heading('Latest Items');
	}
}
