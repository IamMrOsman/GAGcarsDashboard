<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
	protected static ?string $model = User::class;

	protected static ?string $recordTitleAttribute = 'name';

	protected static ?string $navigationGroup = 'User';

	protected static ?string $navigationIcon = 'heroicon-o-users';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				// Forms\Components\FileUpload::make('avatar')
				// 	->image()
				// 	->avatar()
				// 	->default(fn($record) => 'https://ui-avatars.com/api/?name=' . $record->name . '&color=FFFFFF&background=09090b')
				// 	->imageEditor()
				// 	->circleCropper(),
				Forms\Components\TextInput::make('name')
					->required()
					->columnSpanFull()
					->maxLength(255),
				Forms\Components\TextInput::make('email')
					->email()
					->unique(ignoreRecord: true)
					->required()
					->maxLength(255),
				Forms\Components\TextInput::make('phone')
					->tel()
					->unique(ignoreRecord: true)
					->maxLength(255),
				Forms\Components\Hidden::make('password')
					->default(Hash::make('password'))
					->disabledOn('edit')
					->dehydrated(fn($context) => $context === 'create'),
				Forms\Components\Select::make('country_id')
					->columnSpanFull()
					->relationship('country', 'name')
					->preload()
					->searchable(),
				Forms\Components\Select::make('roles')
					->columnSpanFull()
					->relationship('roles', 'name')
					->multiple()
					->preload()
					->searchable(),
				// Forms\Components\Select::make('state_id')
				// 	->columnSpanFull()
				//     ->required()
				// 	->relationship('state', 'name')
				// 	->preload()
				// 	->searchable(),
			]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('id')
					->toggleable(isToggledHiddenByDefault: true)
					->label('ID')
					->searchable(),
				Tables\Columns\ImageColumn::make('avatar')
					->circular()
					->default(fn($record) => 'https://ui-avatars.com/api/?name=' . $record->name . '&color=FFFFFF&background=09090b'),
				Tables\Columns\TextColumn::make('name')
					->searchable(),
				Tables\Columns\TextColumn::make('email')
					->searchable(),
				Tables\Columns\TextColumn::make('phone')
					->searchable(),
				Tables\Columns\TextColumn::make('email_verified_at')
					->toggleable(isToggledHiddenByDefault: true)
					->dateTime()
					->sortable(),
				Tables\Columns\TextColumn::make('two_factor_confirmed_at')
					->toggleable(isToggledHiddenByDefault: true)
					->dateTime()
					->sortable(),
				Tables\Columns\TextColumn::make('profile_photo_path')
					->toggleable(isToggledHiddenByDefault: true)
					->searchable(),
				Tables\Columns\IconColumn::make('paid_seller')
					->boolean(),
				Tables\Columns\TextColumn::make('created_at')
					->dateTime()
					->sortable()
					->toggleable(isToggledHiddenByDefault: true),
				Tables\Columns\TextColumn::make('updated_at')
					->dateTime()
					->sortable()
					->toggleable(isToggledHiddenByDefault: true),
				Tables\Columns\TextColumn::make('country.name')
					->numeric()
					->sortable(),
				// Tables\Columns\TextColumn::make('state.name')
				//     ->numeric()
				//     ->sortable(),
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
			'index' => Pages\ListUsers::route('/'),
			// 'create' => Pages\CreateUser::route('/create'),
			// 'view' => Pages\ViewUser::route('/{record}'),
			// 'edit' => Pages\EditUser::route('/{record}/edit'),
		];
	}

	public static function getEloquentQuery(): Builder
	{
		return parent::getEloquentQuery()
			->withoutGlobalScopes([
				SoftDeletingScope::class,
			]);
	}
}
