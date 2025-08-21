<?php

namespace App\Filament\Clusters\Settings\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Setting;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\Settings\Resources\SettingResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\Settings\Resources\SettingResource\RelationManagers;
use App\Filament\Clusters\Settings;

class SettingResource extends Resource
{
	protected static ?string $model = Setting::class;

	protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

	protected static ?string $cluster = Settings::class;

	protected static ?string $navigationLabel = 'All Other Settings';

	protected static ?string $modelLabel = 'Setting';

	protected static ?string $pluralModelLabel = 'Settings';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				TextInput::make('key_name')
					->label('Name')
					->required()
					->columnSpanFull()
					->unique(ignoreRecord: true)
					->live(debounce: 1000)
					->afterStateUpdated(function (Set $set, $state) {
						if (filled($state)) {
							$set('key_slug', Str::slug($state));
						}
					}),
				TextInput::make('key_slug')
					->label('Slug')
					->required()
					->columnSpanFull()
					->unique(ignoreRecord: true)
					->default(fn(Get $get): string => Str::slug($get('key_name')))
					->reactive()
					->disabled()
					->dehydrated(),
				TextInput::make('value')
					->label('Value')
					->default('null')
					->columnSpanFull(),
				Textarea::make('description')
					->label('Description')
					->columnSpanFull()
					->rows(3),
				KeyValue::make('data')
					->label('Data')
					->keyLabel('Key')
					->valueLabel('Value')
					->columns(2)
					->columnSpanFull(),
				FileUpload::make('file')
					->label('File')
					->directory('settings')
					->visibility('private')
					->downloadable()
					->columnSpanFull(),
			]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				TextColumn::make('key_name')
					->label('Name'),
				TextColumn::make('key_slug')
					->label('Slug'),
				TextColumn::make('value')
					->label('Value'),
				TextColumn::make('description')
					->label('Description')
					->toggleable(isToggledHiddenByDefault: true),
				TextColumn::make('data')
					->label('Data')
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
			])
			->modifyQueryUsing(fn(Builder $query) => $query->where('key_slug', '!=', 'smtp'));
	}

	public static function getPages(): array
	{
		return [
			'index' => Pages\ManageSettings::route('/'),
		];
	}
}
