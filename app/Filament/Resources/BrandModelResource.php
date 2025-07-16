<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\BrandModel;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BrandModelResource\Pages;
use App\Filament\Resources\BrandModelResource\RelationManagers;

class BrandModelResource extends Resource
{
    protected static ?string $model = BrandModel::class;

	protected static ?string $recordTitleAttribute = 'name';

	protected static ?string $navigationLabel = 'Models';

	protected static ?string $modelLabel = 'Model';
	protected static ?string $pluralModelLabel = 'Models';

    protected static ?string $navigationIcon = 'tabler-car-4wd';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('brand_id')
					->columnSpanFull()
                    ->required()
                    ->relationship('brand', 'name')
					->preload()
					->searchable(),
                Forms\Components\TextInput::make('name')
					->columnSpanFull()
                    ->required()
					->live(debounce: 1000)
					->afterStateUpdated(fn (Set $set, Get $get) => $set('slug', Str::slug($get('name'))))
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
					->columnSpanFull()
                    ->required()
					->unique()
					->disabled()
					->dehydrated()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
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
            'index' => Pages\ListBrandModels::route('/'),
            // 'create' => Pages\CreateBrandModel::route('/create'),
            // 'edit' => Pages\EditBrandModel::route('/{record}/edit'),
        ];
    }
}
