<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Item;
use App\Models\Country;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ItemResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ItemResource\RelationManagers;

class ItemResource extends Resource
{
	protected static ?string $model = Item::class;

	protected static ?string $recordTitleAttribute = 'name';

	protected static ?string $navigationIcon = 'tabler-car-suv';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				Forms\Components\TextInput::make('user_id')
					->hidden()
					->default(auth()->id())
					->maxLength(26),
				Forms\Components\Select::make('category_id')
					->columnSpanFull()
					->required()
					->live()
					->relationship('category', 'name')
					->preload()
					->searchable(),
				Forms\Components\Select::make('brand_id')
					// ->required()
					->label('Make')
					->relationship('brand', 'name')
					->preload()
					->searchable(),
				Forms\Components\Select::make('brand_model_id')
					// ->required()
					->label('Model')
					->relationship('brandModel', 'name')
					->preload()
					->searchable(),
				Forms\Components\TextInput::make('name')
					->required()
					->live(debounce: 500)
					->columnSpanFull()
					->afterStateUpdated(fn(Set $set, Get $get) => $set('slug', Str::slug($get('name'))))
					->maxLength(255),
				Forms\Components\Hidden::make('slug')
					->required(),
				Forms\Components\Select::make('country_id')
					->required()
					->label('Country')
					->relationship('country', 'name')
					->preload()
					->searchable()
					->reactive(),
				Forms\Components\TextInput::make('location')
					->placeholder('Enter the location of the item'),
				Forms\Components\Textarea::make('description')
					->columnSpanFull()
					->maxLength(255),
				...self::getDynamicFields(),
				Forms\Components\FileUpload::make('images')
					->columnSpanFull()
					->multiple()
					->image(),
				Forms\Components\CheckboxList::make('features')
					->columnSpanFull()
					->options(function (Get $get) {
						$categoryId = $get('category_id');
						if (!$categoryId) {
							return [];
						}

						$category = \App\Models\Category::find($categoryId);
						if (!$category || !$category->features) {
							return [];
						}

						// Convert the features array to a key-value format for checkboxes
						$options = [];
						foreach ($category->features as $feature) {
							$options[$feature] = ucwords(str_replace('_', ' ', $feature));
						}

						return $options;
					})
					->visible(fn(Get $get): bool => (bool) $get('category_id'))
					->columns(3),
				Forms\Components\TextInput::make('serial_number')
					->columnSpanFull()
					->maxLength(255),
				Forms\Components\Select::make('condition')
					->required()
					->native(false)
					->options([
						'new' => 'BrandNew',
						'used' => 'Used',
					])
					->default('new'),
				Forms\Components\Select::make('status')
					->required()
					->native(false)
					->options([
						'0' => 'Inactive',
						'1' => 'Active',
					])
					->default(0),
				Forms\Components\TextInput::make('price')
					->numeric()
					->prefix(function ($get) {
						$countryId = $get('country_id');
						if ($countryId) {
							$country = Country::find($countryId);
							return $country ? $country->currency_symbol : 'GHC';
						}
						return 'GHC';
					})
					->columnSpanFull()
					->minValue(0)
					->step(0.01),
				Forms\Components\Toggle::make('warranty')
					->label('Warranty')
					->live()
					->default(false),
				Forms\Components\DatePicker::make('warranty_expiration')
					->visible(fn(Get $get): bool => (bool) $get('warranty')),
			]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('id')
					->label('ID')
					->toggleable(isToggledHiddenByDefault: true)
					->searchable(),
				Tables\Columns\TextColumn::make('user.name')
					->searchable(),
				Tables\Columns\TextColumn::make('brandModel.name')
					->numeric()
					->sortable(),
				Tables\Columns\TextColumn::make('brand.name')
					->numeric()
					->sortable(),
				Tables\Columns\TextColumn::make('category.name')
					->numeric()
					->sortable(),
				Tables\Columns\TextColumn::make('name')
					->searchable(),
				Tables\Columns\TextColumn::make('slug')
					->searchable(),
				Tables\Columns\TextColumn::make('description')
					->searchable()
					->toggleable(isToggledHiddenByDefault: true),
				Tables\Columns\TextColumn::make('location')
					->searchable(),
				Tables\Columns\TextColumn::make('serial_number')
					->searchable(),
				Tables\Columns\TextColumn::make('condition')
					->searchable(),
				Tables\Columns\TextColumn::make('status')
					->searchable(),
				Tables\Columns\TextColumn::make('price')
					->formatStateUsing(function ($state, $record) {
						if ($record && $record->country && $state) {
							return $record->country->currency_symbol . ' ' . number_format((float)$state, 2);
						}
						return $state ? 'GHC ' . number_format((float)$state, 2) : '';
					})
					->searchable(),
				Tables\Columns\TextColumn::make('mileage')
					->searchable(),
				Tables\Columns\TextColumn::make('warranty')
					->searchable(),
				Tables\Columns\TextColumn::make('warranty_expiration')
					->searchable(),
				Tables\Columns\TextColumn::make('deleted_at')
					->dateTime()
					->sortable()
					->toggleable(isToggledHiddenByDefault: true),
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
				Tables\Filters\TrashedFilter::make(),
			])
			->actions([
				Tables\Actions\ViewAction::make(),
				Tables\Actions\EditAction::make(),
			])
			->bulkActions([
				Tables\Actions\BulkActionGroup::make([
					Tables\Actions\DeleteBulkAction::make(),
					Tables\Actions\ForceDeleteBulkAction::make(),
					Tables\Actions\RestoreBulkAction::make(),
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
			'index' => Pages\ListItems::route('/'),
			// 'create' => Pages\CreateItem::route('/create'),
			// 'view' => Pages\ViewItem::route('/{record}'),
			// 'edit' => Pages\EditItem::route('/{record}/edit'),
		];
	}

	public static function getEloquentQuery(): Builder
	{
		return parent::getEloquentQuery()
			->withoutGlobalScopes([
				SoftDeletingScope::class,
			]);
	}

	protected static function getDynamicFields(): array
	{
		return [
			Forms\Components\Section::make('Item Information')
				->schema(function (Get $get) {
					$categoryId = $get('category_id');
					if (!$categoryId) {
						return [];
					}

					$category = \App\Models\Category::find($categoryId);
					if (!$category) {
						return [];
					}

					$fields = [];
					foreach ($category->itemFields as $itemField) {
						$field = self::createFormField($itemField);
						if ($field) {
							$fields[] = $field;
						}
					}

					return $fields;
				})
				->visible(fn(Get $get): bool => (bool) $get('category_id'))
				->collapsible()
				->collapsed(false),
		];
	}

	protected static function createFormField($itemField)
	{
		$fieldName = $itemField->name;
		$label = $itemField->label;
		$type = $itemField->type;
		$required = $itemField->required;
		$nullable = $itemField->nullable;
		$options = $itemField->options;

		$field = null;

		switch ($type) {
			case 'string':
				$field = Forms\Components\TextInput::make($fieldName)
					->label($label)
					->required($required)
					->nullable($nullable);
				break;

			case 'number':
				$field = Forms\Components\TextInput::make($fieldName)
					->label($label)
					->numeric()
					->required($required)
					->nullable($nullable);
				break;

			case 'year':
				$field = Forms\Components\TextInput::make($fieldName)
					->label($label)
					->numeric()
					->minValue(1900)
					->maxValue(date('Y'))
					->default(date('Y'))
					->required($required)
					->nullable($nullable);
				break;

			case 'date':
				$field = Forms\Components\DatePicker::make($fieldName)
					->label($label)
					->required($required)
					->nullable($nullable);
				break;

			case 'enum':
				if ($options && is_array($options)) {
					$field = Forms\Components\Select::make($fieldName)
						->label($label)
						->options($options)
						->native(false)
						->required($required)
						->nullable($nullable);
				}
				break;

			case 'json':
				if ($fieldName === 'images') {
					$field = Forms\Components\FileUpload::make($fieldName)
						->label($label)
						->multiple()
						->image()
						->required($required)
						->nullable($nullable);
				} elseif ($fieldName === 'features' && $options && is_array($options)) {
					$field = Forms\Components\CheckboxList::make($fieldName)
						->label($label)
						->options($options)
						->columns(3)
						->required($required)
						->nullable($nullable);
				} else {
					$field = Forms\Components\Textarea::make($fieldName)
						->label($label)
						->required($required)
						->nullable($nullable);
				}
				break;

			default:
				$field = Forms\Components\TextInput::make($fieldName)
					->label($label)
					->required($required)
					->nullable($nullable);
				break;
		}

		return $field;
	}
}
