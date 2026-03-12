<?php

namespace App\Filament\Widgets;

use App\Models\CategoryRequirement;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ItemSettingsWidget extends TableWidget
{
	protected static ?int $sort = 20;
	protected int|string|array $columnSpan = ['default' => 'full'];

	public function table(Table $table): Table
	{
		return $table
			->query(
				CategoryRequirement::query()
					->with(['country', 'category'])
			)
			->columns([
				Tables\Columns\TextColumn::make('country.name')
					->label('Country')
					->sortable()
					->searchable(),
				Tables\Columns\TextColumn::make('category.name')
					->label('Category')
					->sortable()
					->searchable(),
				Tables\Columns\IconColumn::make('require_approval')
					->label('Approval Required')
					->boolean(),
				Tables\Columns\IconColumn::make('require_payment')
					->label('Payment Required')
					->boolean(),
			])
			->heading('Listing Requirements by Country & Category');
	}
}

