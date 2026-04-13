<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpecialOfferResource\Pages;
use App\Filament\Resources\SpecialOfferResource\RelationManagers;
use App\Models\SpecialOffer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SpecialOfferResource extends Resource
{
    protected static ?string $model = SpecialOffer::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
					->required()
					->default(auth()->id()),
                Forms\Components\Select::make('item_id')
					->columnSpanFull()
                    ->required()
					->relationship('item', 'name')
					->preload()
					->searchable(),
                Forms\Components\DateTimePicker::make('start_at'),
                Forms\Components\DateTimePicker::make('end_at'),
                Forms\Components\Select::make('status')
					->columnSpanFull()
                    ->required()
					->native(false)
					->options([
						'0' => 'Inactive',
						'1' => 'Active',
					])
                    ->default(0),
                Forms\Components\TextInput::make('discount')
					->columnSpanFull()
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('discount_type')
					->columnSpanFull()
                    ->required()
					->native(false)
					->options([
						'percentage' => 'Percentage',
						'fixed' => 'Fixed',
					])
                    ->default('percentage'),
                Forms\Components\Textarea::make('description')
					->columnSpanFull()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('discount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
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
            'index' => Pages\ListSpecialOffers::route('/'),
            // 'create' => Pages\CreateSpecialOffer::route('/create'),
            // 'edit' => Pages\EditSpecialOffer::route('/{record}/edit'),
        ];
    }
}
