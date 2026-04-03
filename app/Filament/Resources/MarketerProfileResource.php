<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarketerProfileResource\Pages;
use App\Models\MarketerProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MarketerProfileResource extends Resource
{
    protected static ?string $model = MarketerProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Marketer commissions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name', fn ($query) => $query->role('marketer'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('commission_rate')
                    ->numeric()
                    ->step(0.0001)
                    ->minValue(0)
                    ->maxValue(1)
                    ->default(0)
                    ->helperText('Decimal rate on net revenue, e.g. 0.10 = 10%.'),
                Forms\Components\Toggle::make('active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Marketer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->numeric(decimalPlaces: 4),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMarketerProfiles::route('/'),
        ];
    }
}
