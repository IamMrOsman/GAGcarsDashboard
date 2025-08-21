<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\FaqCategory;
use Illuminate\Support\Str;
use App\Filament\Clusters\Faq;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\FaqCategoryResource\Pages;
use App\Filament\Resources\FaqCategoryResource\RelationManagers;

class FaqCategoryResource extends Resource
{
    protected static ?string $model = FaqCategory::class;

	protected static ?string $cluster = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
					->live(debounce: 1000)
					->afterStateUpdated(fn(Set $set, Get $get) => $set('slug', Str::slug($get('name')))),
                Forms\Components\TextInput::make('slug')
                    ->required()
					->unique()
					->disabled()
					->dehydrated()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535),
                Forms\Components\Select::make('status')
                    ->options([
						'active' => 'Active',
						'inactive' => 'Inactive',
					])
					->native(false)
                    ->default('active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('description')
					->limit(50),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
					->toggleable(isToggledHiddenByDefault: true)
					->toggledHiddenByDefault(true)
					->dateTime('d-m-Y H:i'),
                Tables\Columns\TextColumn::make('updated_at')
					->toggleable(isToggledHiddenByDefault: true)
					->toggledHiddenByDefault(true)
					->dateTime('d-m-Y H:i'),
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
            'index' => Pages\ListFaqCategories::route('/'),
            // 'create' => Pages\CreateFaqCategory::route('/create'),
            // 'edit' => Pages\EditFaqCategory::route('/{record}/edit'),
        ];
    }
}
