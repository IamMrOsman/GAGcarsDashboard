<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WishListResource\Pages;
use App\Filament\Resources\WishListResource\RelationManagers;
use App\Models\WishList;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\ItemResource;

class WishListResource extends Resource
{
	protected static ?string $model = WishList::class;

	protected static ?string $navigationIcon = 'heroicon-o-heart';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				//
			]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('user.name')
					->url(fn(WishList $record): string => UserResource::getUrl('edit', ['record' => $record->user_id]))
					->openUrlInNewTab(),
				Tables\Columns\TextColumn::make('item.name')
					->url(fn(WishList $record): string => ItemResource::getUrl('edit', ['record' => $record->item_id]))
					->openUrlInNewTab(),
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
			'index' => Pages\ManageWishLists::route('/'),
		];
	}
}
