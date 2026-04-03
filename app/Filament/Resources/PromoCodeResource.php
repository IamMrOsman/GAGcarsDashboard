<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\Package;
use App\Models\PromoCode;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'marketing promo code';

    protected static ?string $pluralModelLabel = 'marketing promo codes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(64)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state) => $state ? strtoupper(trim($state)) : $state)
                    ->helperText('Stored uppercase. Must be unique.'),
                Forms\Components\Select::make('marketer_id')
                    ->label('Marketer')
                    ->relationship('marketer', 'name', fn (Builder $query) => $query->role('marketer'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('discount_type')
                    ->options([
                        PromoCode::DISCOUNT_PERCENT => 'Percentage',
                        PromoCode::DISCOUNT_FIXED => 'Fixed amount (GHS)',
                    ])
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('discount_value')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->helperText('Percent (0–100) or fixed GHS depending on type.'),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->nullable(),
                Forms\Components\TextInput::make('max_uses')
                    ->numeric()
                    ->minValue(1)
                    ->nullable()
                    ->helperText('Leave empty for unlimited.'),
                Forms\Components\Toggle::make('active')
                    ->default(true),
                Forms\Components\Select::make('package_id')
                    ->label('Limit to package (optional)')
                    ->relationship('package', 'name', fn (Builder $query) => $query->where('package_type', 'upload'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('marketer.name')
                    ->label('Marketer')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_type'),
                Tables\Columns\TextColumn::make('discount_value')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('uses_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_uses')
                    ->placeholder('∞'),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
