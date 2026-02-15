<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopSellerUsersTable extends TableWidget
{
	protected static ?int $sort = 15;
	protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

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
					->searchable()
					->limit(25),
				TextColumn::make('items_count')
					->label('Listings')
					->sortable()
					->badge()
					->color('primary'),
			])
			->heading('Top Sellers');
	}
}
