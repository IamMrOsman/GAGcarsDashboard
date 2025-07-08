<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemFieldResource\Pages;
use App\Filament\Resources\ItemFieldResource\RelationManagers;
use App\Models\ItemField;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemFieldResource extends Resource
{
	protected static ?string $model = ItemField::class;

	protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				Forms\Components\TextInput::make('name')
					->required()
					->columnSpan(2)
					->unique(ignoreRecord: true)
					->maxLength(255),
				Forms\Components\TextInput::make('label')
					->required()
					->columnSpan(2)
					->maxLength(255),
				Forms\Components\Select::make('type')
					->required()
					->live()
					->columnSpan(2)
					->native(false)
					->options([
						'string' => 'String',
						'number' => 'Number',
						'boolean' => 'Boolean',
						'json' => 'JSON',
						'enum' => 'Enum',
						'year' => 'Year',
						'date' => 'Date',
						'time' => 'Time',
						'datetime' => 'Datetime',
						'text' => 'Text',
						'longtext' => 'Long Text',
					]),
				Forms\Components\Toggle::make('unique')
					->columnSpan(3)
					->required(),
				Forms\Components\Toggle::make('nullable')
					->columnSpan(3)
					->required(),
				Forms\Components\KeyValue::make('options')
					->columnSpanFull()
					->visible(fn($get) => $get('type') === 'enum')
					->required(fn($get) => $get('type') === 'enum'),
			])
			->columns(6);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('name'),
				Tables\Columns\TextColumn::make('label'),
				Tables\Columns\TextColumn::make('type'),
				Tables\Columns\ToggleColumn::make('unique'),
				Tables\Columns\ToggleColumn::make('nullable'),
				Tables\Columns\TextColumn::make('options'),
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
			'index' => Pages\ListItemFields::route('/'),
			// 'create' => Pages\CreateItemField::route('/create'),
			// 'edit' => Pages\EditItemField::route('/{record}/edit'),
		];
	}
}
