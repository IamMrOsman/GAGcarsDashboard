<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BroadcastResource\Pages;
use App\Filament\Resources\BroadcastResource\RelationManagers;
use App\Models\Broadcast;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BroadcastResource extends Resource
{
    protected static ?string $model = Broadcast::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
				Forms\Components\Hidden::make('user_id')
					->default(auth()->id()),
                Forms\Components\TextInput::make('subject')
                    ->required()
					->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->required()
					->columnSpanFull()
                    ->rows(5),
                Forms\Components\FileUpload::make('image')
					->columnSpanFull()
                    ->image(),
                Forms\Components\Select::make('target')
                    ->options([
                        'customers' => 'Customers',
                        'dealers' => 'Dealers',
                        'all' => 'All',
                    ])
                    ->default('customers'),
				Forms\Components\Select::make('country_id')
					->relationship('country', 'name')
					->preload()
					->searchable(),
                Forms\Components\DateTimePicker::make('scheduled_at'),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'sent' => 'Sent',
                    ])
                    ->default('draft'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('subject'),
                Tables\Columns\TextColumn::make('target'),
                Tables\Columns\TextColumn::make('country.name'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('scheduled_at'),
                Tables\Columns\TextColumn::make('created_at'),
                Tables\Columns\TextColumn::make('updated_at'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('target')
                    ->options([
                        'customers' => 'Customers',
                        'dealers' => 'Dealers',
                        'all' => 'All',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'sent' => 'Sent',
                    ]),
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
            'index' => Pages\ManageBroadcasts::route('/'),
        ];
    }
}
