<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Item;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ItemResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ItemResource\RelationManagers;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

	protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'tabler-car-suv';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
					->hidden()
					->default(auth()->id())
                    ->maxLength(26),
				Forms\Components\Select::make('brand_id')
					->required()
					->relationship('brand', 'name')
					->preload()
					->searchable(),
                Forms\Components\Select::make('brand_model_id')
                    ->required()
					->relationship('brandModel', 'name')
					->preload()
					->searchable(),
                Forms\Components\Select::make('category_id')
					->columnSpanFull()
                    ->required()
					->relationship('category', 'name')
					->preload()
					->searchable(),
                Forms\Components\TextInput::make('name')
                    ->required()
					->live()
					->afterStateUpdated(fn (Set $set, Get $get) => $set('slug', Str::slug($get('name'))))
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
					->unique()
					->disabled()
					->dehydrated()
                    ->maxLength(255),
				Forms\Components\Textarea::make('description')
					->columnSpanFull()
					->maxLength(255),
				Forms\Components\TextInput::make('year')
					->required()
					->numeric()
					->minValue(1900)
					->maxValue(date('Y'))
					->default(date('Y')),
				Forms\Components\TextInput::make('engine_capacity')
					->required()
					->numeric()
					->minValue(0)
					->maxValue(10000)
					->default(0),
				Forms\Components\Select::make('transmission')
					->required()
					->native(false)
					->options([
						'manual' => 'Manual',
						'automatic' => 'Automatic',
					])
					->default('manual'),
				Forms\Components\Select::make('steer_position')
					->required()
					->native(false)
					->options([
						'left' => 'Left',
						'right' => 'Right',
					])
					->default('left'),
				Forms\Components\Select::make('build_type')
					->required()
					->native(false)
					->options([
						'sedan' => 'Sedan',
						'hatchback' => 'Hatchback',
						'suv' => 'SUV',
						'mpv' => 'MPV',
						'coupe' => 'Coupe',
						'convertible' => 'Convertible',
						'pickup' => 'Pickup',
						'van' => 'Van',
						'other' => 'Other',
					])
					->default('sedan'),
				Forms\Components\TextInput::make('number_of_passengers')
					->required()
					->numeric()
					->minValue(1)
					->maxValue(100)
					->default(1),
                Forms\Components\FileUpload::make('images')
					->columnSpanFull()
                    ->multiple()
                    ->image(),
                Forms\Components\KeyValue::make('location')
					->columnSpanFull()
					->default([
						'longitude' => '',
						'latitude' => '',
					])
					->addable(false)
					->deletable(false),
				Forms\Components\KeyValue::make('features')
					->columnSpanFull()
					->default([
						'air_conditioning' => '',
						'dashboard_camera' => '',
						'power_windows' => '',
						'power_locks' => '',
						'power_mirrors' => '',
						'power_steering' => '',
						'power_seats' => '',
					])
					->addable(false)
					->deletable(false),
                Forms\Components\TextInput::make('serial_number')
					->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\Select::make('condition')
                    ->required()
					->native(false)
					->options([
						'new' => 'BrandNew',
						'used' => 'Used',
					])
                    ->default('new'),
                Forms\Components\Select::make('status')
                    ->required()
					->native(false)
					->options([
						'0' => 'Inactive',
						'1' => 'Active',
					])
                    ->default(0),
                Forms\Components\TextInput::make('price')
					->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\TextInput::make('mileage')
					->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\TextInput::make('warranty')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('warranty_expiration'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
					->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('brandModel.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
					->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('condition')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mileage')
                    ->searchable(),
                Tables\Columns\TextColumn::make('warranty')
                    ->searchable(),
                Tables\Columns\TextColumn::make('warranty_expiration')
                    ->searchable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListItems::route('/'),
            // 'create' => Pages\CreateItem::route('/create'),
            // 'view' => Pages\ViewItem::route('/{record}'),
            // 'edit' => Pages\EditItem::route('/{record}/edit'),
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
