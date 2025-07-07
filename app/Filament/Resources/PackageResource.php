<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Filament\Resources\PackageResource\RelationManagers;
use App\Models\Package;
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
					->columnSpanFull()
					->maxLength(255),
				Forms\Components\Textarea::make('description')
					->maxLength(65535)
					->columnSpanFull(),
				Forms\Components\TextInput::make('price')
					->required()
					->numeric()
					->prefix('GHC')
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
				Tables\Columns\TextColumn::make('description')
					->limit(50)
					->searchable(),
				Tables\Columns\TextColumn::make('price')
					->money('USD')
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
