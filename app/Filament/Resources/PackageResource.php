<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Filament\Resources\PackageResource\RelationManagers;
use App\Models\Package;
use App\Models\Country;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackageResource extends Resource
{
	protected static ?string $model = Package::class;

	protected static ?string $navigationIcon = 'heroicon-o-cube';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				Forms\Components\TextInput::make('name')
					->required()
					->label('Package Name')
					->columnSpanFull(),
				Forms\Components\Select::make('country_id')
					->relationship('country', 'name')
					->preload()
					->required()
					->searchable()
					->reactive(),
				Forms\Components\Select::make('category_id')
					->relationship('category', 'name')
					->preload()
					->required()
					->searchable()
					->reactive(),
				Forms\Components\Textarea::make('description')
					->maxLength(65535)
					->columnSpanFull(),
				Forms\Components\TextInput::make('price')
					->required()
					->numeric()
					->prefix(function ($get) {
						$countryId = $get('country_id');
						if ($countryId) {
							$country = Country::find($countryId);
							return $country ? $country->currency_symbol : 'GHC';
						}
						return 'GHC';
					})
					->required()
					->minValue(0)
					->step(0.01),
				Forms\Components\TextInput::make('number_of_listings')
					->required()
					->numeric()
					->minValue(1),
			]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('name')
					->searchable(),
				Tables\Columns\TextColumn::make('country.name'),
				Tables\Columns\TextColumn::make('category.name'),
				Tables\Columns\TextColumn::make('description')
					->limit(50)
					->searchable(),
				Tables\Columns\TextColumn::make('price')
					->formatStateUsing(function ($state, $record) {
						if ($record && $record->country) {
							return $record->country->currency_symbol . ' ' . number_format($state, 2);
						}
						return 'GHC ' . number_format($state, 2);
					})
					->sortable(),
				Tables\Columns\TextColumn::make('number_of_listings')
					->numeric()
					->sortable(),
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
				Tables\Actions\DeleteAction::make(),
			])
			->bulkActions([
				Tables\Actions\BulkActionGroup::make([
					Tables\Actions\DeleteBulkAction::make(),
				]),
			]);
	}

	public static function getPages(): array
	{
		return [
			'index' => Pages\ManagePackages::route('/'),
		];
	}
}
