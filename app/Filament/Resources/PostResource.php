<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource\RelationManagers;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class PostResource extends Resource
{
	protected static ?string $model = Post::class;

	protected static ?string $navigationIcon = 'heroicon-o-pencil';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				Forms\Components\Hidden::make('user_id')
					->default(auth()->user()->id),
				Forms\Components\TextInput::make('title')
					->required()
					->maxLength(255)
					->live(onBlur: true)
					->afterStateUpdated(fn(string $state, callable $set) => $set('slug', Str::slug($state))),
				Forms\Components\TextInput::make('slug')
					->required()
					->maxLength(255)
					->unique(ignoreRecord: true),
				Forms\Components\Textarea::make('description')
					->rows(5)
					->columnSpanFull(),
				Forms\Components\RichEditor::make('content')
					->required()
					->columnSpanFull(),
				Forms\Components\TagsInput::make('tags')
					->helperText('Press enter to add a tag'),
				Forms\Components\FileUpload::make('image')
					->image()
					->required()
					->imageEditor(),
			]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\ImageColumn::make('image')
					->circular(),
				Tables\Columns\TextColumn::make('title')
					->searchable()
					->sortable()
					->limit(50),
				Tables\Columns\TextColumn::make('slug')
					->searchable()
					->limit(30),
				Tables\Columns\TextColumn::make('description')
					->limit(50)
					->searchable()
					->toggleable(),
				Tables\Columns\TextColumn::make('tags')
					->badge()
					->separator(',')
					->limit(3),
				Tables\Columns\TextColumn::make('user.name')
					->label('Author')
					->sortable()
					->searchable(),
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
				Tables\Actions\ViewAction::make(),
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
			'index' => Pages\ListPosts::route('/'),
			'create' => Pages\CreatePost::route('/create'),
			'edit' => Pages\EditPost::route('/{record}/edit'),
		];
	}
}
