<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\ViewAction;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Filament\Resources\ItemResource;
use App\Models\Country;

class LatestItems extends BaseWidget
{
	protected static ?int $sort = 6;
	protected int|string|array $columnSpan = 'full';

	public function table(Table $table): Table
	{
		return $table
			->query(fn() => Item::latest()->limit(5))
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
			->actions([
				ViewAction::make()
					->form(function ($record, $livewire) {
						return $this->getItemFormSchema($livewire);
					})
					->modalWidth('7xl'),
			])
			->heading('Latest Items');
	}

	protected function getItemFormSchema($livewire): array
	{
		// Create a form instance using the Livewire component
		$form = \Filament\Forms\Form::make($livewire)
			->schema([]);
		
		// Get the ItemResource form schema
		$itemForm = ItemResource::form($form);
		$schema = $itemForm->getSchema();
		
		// Make all fields read-only
		$this->makeFieldsReadOnly($schema);
		
		return $schema;
	}

	protected function makeFieldsReadOnly(array $components): void
	{
		foreach ($components as $component) {
			if (method_exists($component, 'disabled')) {
				$component->disabled();
			}
			if (method_exists($component, 'dehydrated')) {
				$component->dehydrated(false);
			}
			
			// Handle nested components (like Sections, Repeaters, etc.)
			if (method_exists($component, 'getChildComponents')) {
				$this->makeFieldsReadOnly($component->getChildComponents());
			}
			if (method_exists($component, 'getSchema')) {
				$this->makeFieldsReadOnly($component->getSchema());
			}
		}
	}
}
