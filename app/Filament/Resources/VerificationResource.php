<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VerificationResource\Pages;
use App\Filament\Resources\VerificationResource\RelationManagers;
use App\Models\Verification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class VerificationResource extends Resource
{
	protected static ?string $model = Verification::class;

	protected static ?string $navigationIcon = 'heroicon-o-check-badge';

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				Forms\Components\Select::make('user_id')
					->required()
					->relationship('user', 'name')
					->searchable()
					->columnSpan(2)
					->preload(),
				Forms\Components\Select::make('verification_type')
					->options([
						'individual' => 'Individual',
						'dealer' => 'Dealer',
					])
					->native(false)
					->columnSpan(2)
					->required(),
				Forms\Components\TextInput::make('dealership_name')
					->columnSpan(2)
					->maxLength(255)
					->visible(fn($get) => $get('verification_type') === 'dealer'),
				Forms\Components\TextInput::make('address')
					->columnSpan(2)
					->maxLength(255),
				Forms\Components\FileUpload::make('dealership_registration_document')
					->image()
					->imageEditor()
					->imageEditorAspectRatios([
						'16:9',
						'4:3',
						'1:1',
					])
					->columnSpan(2)
					->visible(fn($get) => $get('verification_type') === 'dealer')
					->getUploadedFileUsing(fn (Forms\Components\BaseFileUpload $component, string $file): ?array => Str::startsWith($file, 'http') ? ['url' => $file, 'name' => 'Image', 'size' => 0, 'type' => 'image/jpeg'] : null),
				Forms\Components\Select::make('document_type')
					->options([
						'passport' => 'Passport',
						'national_id' => 'National ID',
						'driving_license' => 'Driving License',
					])
					->native(false)
					->columnSpan(2)
					->required(),
				Forms\Components\TextInput::make('document_number')
					->columnSpan(2)
					->maxLength(255),
				Forms\Components\FileUpload::make('document_front')
					->image()
					->imageEditor()
					->imageEditorAspectRatios([
						'16:9',
						'4:3',
						'1:1',
					])
					->columnSpan(2)
					->getUploadedFileUsing(fn (Forms\Components\BaseFileUpload $component, string $file): ?array => Str::startsWith($file, 'http') ? ['url' => $file, 'name' => 'Image', 'size' => 0, 'type' => 'image/jpeg'] : null),
				Forms\Components\FileUpload::make('document_back')
					->image()
					->imageEditor()
					->imageEditorAspectRatios([
						'16:9',
						'4:3',
						'1:1',
					])
					->columnSpan(2)
					->getUploadedFileUsing(fn (Forms\Components\BaseFileUpload $component, string $file): ?array => Str::startsWith($file, 'http') ? ['url' => $file, 'name' => 'Image', 'size' => 0, 'type' => 'image/jpeg'] : null),
				Forms\Components\FileUpload::make('selfie')
					->image()
					->imageEditor()
					->imageEditorAspectRatios([
						'16:9',
						'4:3',
						'1:1',
					])
					->columnSpan(2)
					->getUploadedFileUsing(fn (Forms\Components\BaseFileUpload $component, string $file): ?array => Str::startsWith($file, 'http') ? ['url' => $file, 'name' => 'Image', 'size' => 0, 'type' => 'image/jpeg'] : null),
				Forms\Components\Select::make('status')
					->options([
						'pending' => 'Pending',
						'verified' => 'Verified',
						'rejected' => 'Rejected',
					])
					->native(false)
					->columnSpan(3)
					->required(),
				Forms\Components\Textarea::make('comment')
					->columnSpan(3),
				Forms\Components\Select::make('verified_by')
					->label('Verified By')
					->relationship('user', 'name')
					->searchable()
					->columnSpan(3)
					->preload(),
				Forms\Components\Select::make('rejected_by')
					->label('Rejected By')
					->relationship('user', 'name')
					->searchable()
					->columnSpan(3)
					->preload(),
				Forms\Components\DateTimePicker::make('approved_at')
					->columnSpan(3),
				Forms\Components\DateTimePicker::make('rejected_at')
					->columnSpan(3),
			])
			->columns(6);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('user.name')
					->searchable(),
				Tables\Columns\TextColumn::make('verification_type')
					->searchable(),
				Tables\Columns\TextColumn::make('dealership_name')
					->searchable(),
				Tables\Columns\TextColumn::make('address')
					->searchable(),
				Tables\Columns\ImageColumn::make('dealership_registration_document')
					->searchable(),
				Tables\Columns\TextColumn::make('document_type')
					->searchable(),
				Tables\Columns\TextColumn::make('document_number')
					->searchable(),
				Tables\Columns\ImageColumn::make('document_front')
					->searchable(),
				Tables\Columns\ImageColumn::make('document_back')
					->searchable(),
				Tables\Columns\ImageColumn::make('selfie')
					->searchable(),
				Tables\Columns\TextColumn::make('status')
					->badge()
					->color(fn(string $state): string => match ($state) {
						'pending' => 'warning',
						'verified' => 'success',
						'rejected' => 'danger',
						default => 'gray',
					})
					->searchable(),
				Tables\Columns\TextColumn::make('verified_by')
					->searchable(),
				Tables\Columns\TextColumn::make('rejected_by')
					->searchable(),
				Tables\Columns\TextColumn::make('approved_at')
					->dateTime()
					->sortable(),
				Tables\Columns\TextColumn::make('rejected_at')
					->dateTime()
					->sortable(),
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
			'index' => Pages\ListVerifications::route('/'),
			'create' => Pages\CreateVerification::route('/create'),
			'edit' => Pages\EditVerification::route('/{record}/edit'),
		];
	}
}
