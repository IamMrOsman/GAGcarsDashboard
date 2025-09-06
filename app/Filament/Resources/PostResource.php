<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Post;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PostResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PostResource\RelationManagers;
use App\Filament\Clusters\Post as PostCluster;

class PostResource extends Resource
{
	protected static ?string $model = Post::class;

	protected static ?string $cluster = PostCluster::class;

	protected static ?string $navigationIcon = 'heroicon-o-pencil';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				Forms\Components\Hidden::make('user_id')
					->default(auth()->user()->id),
				Forms\Components\Select::make('country_id')
					->relationship('country', 'name')
					->preload()
					->searchable()
					->columnSpanFull()
					->reactive()
					->required(),
				Forms\Components\Select::make('category_id')
					->relationship('category', 'name')
					->preload()
					->searchable()
					->columnSpanFull()
					->reactive()
					->required(),
				Forms\Components\TextInput::make('title')
					->required()
					->columnSpanFull()
					->maxLength(255)
					->live(debounce: 1000)
					->afterStateUpdated(fn(Set $set, Get $get) => $set('slug', Str::slug($get('title')))),
				Forms\Components\Hidden::make('slug')
					->required(),
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
				Tables\Columns\TextColumn::make('category.name')
					->label('Category')
					->sortable()
					->searchable(),
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
				Tables\Filters\SelectFilter::make('category_id')
					->relationship('category', 'name')
					->preload()
					->searchable(),
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
			// 'create' => Pages\CreatePost::route('/create'),
			// 'edit' => Pages\EditPost::route('/{record}/edit'),
		];
	}
}
