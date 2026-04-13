<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
	protected static ?string $model = Category::class;

	protected static ?string $recordTitleAttribute = 'name';

	protected static ?string $navigationIcon = 'iconpark-branch';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				Forms\Components\Hidden::make('user_id')
					->required()
					->default(auth()->id()),
				Forms\Components\TextInput::make('name')
					->required()
					->columnSpanFull()
					->live(debounce: 1000)
					->maxLength(255)
					->afterStateUpdated(fn(Set $set, Get $get) => $set('slug', Str::slug($get('name')))),
				Forms\Components\Hidden::make('slug')
					->required(),
				Forms\Components\Select::make('parent_id')
					->label('Parent Category')
					->columnSpanFull()
					->relationship('parent', 'name')
					->preload()
					->searchable(),
				Forms\Components\Textarea::make('description')
					->columnSpanFull()
					->maxLength(255),
				Forms\Components\Checkbox::make('require_make')
					->label('Require Make')
					->default(false),
				Forms\Components\Checkbox::make('require_model')
					->label('Require Model')
					->default(false),
				Forms\Components\Select::make('itemFields')
					->columnSpanFull()
					->label('Available Fields')
					->relationship('itemFields', 'label')
					->multiple()
					->preload()
					->searchable()
					->live()
					->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
						// Get currently highlighted fields
						$currentHighlights = $get('highlightedFields') ?? [];

						// Only keep highlights for fields that are still available
						$newHighlights = array_intersect($currentHighlights, $state ?? []);

						// Update the highlighted fields to only include those still available
						$set('highlightedFields', $newHighlights);
					})
					->helperText('Select the fields that should be available for items in this category')
					->getOptionLabelFromRecordUsing(function ($record) {
						return $record->label . ' (' . $record->type . ', ' . ($record->required ? 'required' : 'nullable') . ')';
					}),
				Forms\Components\CheckboxList::make('highlightedFields')
					->columnSpanFull()
					->label('Highlighted Fields')
					->columns(3)
					->options(function (Get $get, $record) {
						// Get the selected item field IDs from the form or existing record
						$selectedItemFieldIds = $get('itemFields') ?? ($record ? $record->itemFields()->pluck('item_fields.id')->toArray() : []);

						// Return only the selected item fields as options
						return \App\Models\ItemField::whereIn('id', $selectedItemFieldIds)
							->pluck('label', 'id');
					})
					->helperText('Select which of the available fields should be highlighted for this category')
					->afterStateHydrated(function (Forms\Components\CheckboxList $component, $state, $record) {
						if ($record) {
							$highlightedIds = $record->itemFields()->wherePivot('highlight', true)->pluck('item_fields.id')->toArray();
							$component->state($highlightedIds);
						}
					})
					->dehydrated(false)
					->afterStateUpdated(function ($state, $record) {
						if ($record) {
							// First, set all existing relationships to highlight = false
							$record->itemFields()->updateExistingPivot(
								$record->itemFields()->pluck('item_fields.id')->toArray(),
								['highlight' => false]
							);

							// Then set the selected ones to highlight = true
							if ($state) {
								$record->itemFields()->updateExistingPivot($state, ['highlight' => true]);
							}
						}
					})
					->live(),
				Forms\Components\TagsInput::make('features')
					->columnSpanFull()
					->label('Features')
					->placeholder('Feature name')
					->helperText('Enter the features that are available for items in this category. You can add more than one feature by typing and pressing enter.'),
				Forms\Components\FileUpload::make('image')
					->columnSpanFull()
					->image(),
			]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->defaultSort('created_at', 'desc')
			->columns([
				Tables\Columns\TextColumn::make('name')
					->searchable(),
				Tables\Columns\TextColumn::make('parent.name')
					->label('Parent Category')
					->numeric()
					->sortable(),
				Tables\Columns\TextColumn::make('slug')
					->searchable(),
				Tables\Columns\TextColumn::make('description')
					->searchable(),
				Tables\Columns\ImageColumn::make('image'),
				Tables\Columns\TextColumn::make('created_at')
					->dateTime()
					->sortable()
					->toggleable(isToggledHiddenByDefault: true),
				Tables\Columns\TextColumn::make('updated_at')
					->dateTime()
					->sortable()
					->toggleable(isToggledHiddenByDefault: true),
			])
			->filters([
				//
			])
			->actions([
				Tables\Actions\EditAction::make(),
			])
			->bulkActions([
				Tables\Actions\BulkActionGroup::make([
					Tables\Actions\DeleteBulkAction::make(),
				]),
			]);
	}

	public static function getRelations(): array
	{
		return [
			//
		];
	}

	public static function getPages(): array
	{
		return [
			'index' => Pages\ListCategories::route('/'),
			// 'create' => Pages\CreateCategory::route('/create'),
			// 'edit' => Pages\EditCategory::route('/{record}/edit'),
		];
	}
}
