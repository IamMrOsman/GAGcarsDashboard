<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Faq as FaqCluster;
use App\Filament\Resources\FaqResource\Pages;
use App\Filament\Resources\FaqResource\RelationManagers;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

	protected static ?string $cluster = FaqCluster::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->user()->id),
				Forms\Components\Select::make('category_id')
					->relationship('category', 'name')
					->preload()
					->required()
					->columnSpanFull()
					->searchable(),
                Forms\Components\TextInput::make('question')
                    ->required()
					->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\Textarea::make('answer')
                    ->required()
					->columnSpanFull()
                    ->rows(5),
                Forms\Components\FileUpload::make('image')
					->imageEditor()
					->columnSpanFull(),
				Forms\Components\TagsInput::make('tags')
					->hint('Press enter to add a tag'),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ])
                    ->default('draft'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question'),
				Tables\Columns\TextColumn::make('category.name'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at'),
                Tables\Columns\TextColumn::make('updated_at'),
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
            'index' => Pages\ManageFaqs::route('/'),
        ];
    }
}
