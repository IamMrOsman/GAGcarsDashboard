<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VerificationResource\Pages;
use App\Filament\Resources\VerificationResource\RelationManagers;
use App\Models\Verification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
					->relationship('verifiedBy', 'name')
					->searchable()
					->columnSpan(3)
					->preload()
					->disabled()
					->dehydrated(true)
					->default(fn () => auth()->id()),
				Forms\Components\Select::make('rejected_by')
					->label('Rejected By')
					->relationship('rejectedBy', 'name')
					->searchable()
					->columnSpan(3)
					->preload()
					->disabled()
					->dehydrated(true)
					->default(fn () => auth()->id()),
				Forms\Components\DateTimePicker::make('approved_at')
					->columnSpan(3),
				Forms\Components\DateTimePicker::make('rejected_at')
					->columnSpan(3),
			])
			->columns(6);
	}

	public static function infolist(Infolist $infolist): Infolist
	{
		return $infolist
			->schema([
				Section::make('Applicant & type')
					->schema([
						TextEntry::make('user.name')
							->label('User'),
						TextEntry::make('verification_type')
							->badge()
							->color(fn (string $state): string => match ($state) {
								'individual' => 'gray',
								'dealer' => 'info',
								default => 'gray',
							}),
						TextEntry::make('dealership_name')
							->visible(fn ($record) => $record?->verification_type === 'dealer'),
						TextEntry::make('address'),
					])
					->columns(2),
				Section::make('Document')
					->schema([
						TextEntry::make('document_type'),
						TextEntry::make('document_number'),
						ImageEntry::make('document_front')
							->height(240)
							->simpleLightbox(),
						ImageEntry::make('document_back')
							->height(240)
							->simpleLightbox(),
						ImageEntry::make('selfie')
							->label('Selfie')
							->height(240)
							->simpleLightbox(),
					])
					->columns(2),
				Section::make('Dealer documents')
					->schema([
						ImageEntry::make('dealership_registration_document')
							->height(240)
							->simpleLightbox(),
					])
					->visible(fn ($record) => $record?->verification_type === 'dealer')
					->columns(1),
				Section::make('Status & review')
					->schema([
						TextEntry::make('status')
							->badge()
							->color(fn (string $state): string => match ($state) {
								'pending' => 'warning',
								'verified' => 'success',
								'rejected' => 'danger',
								default => 'gray',
							}),
						TextEntry::make('comment')
							->columnSpanFull(),
						TextEntry::make('verifiedBy.name')
							->label('Verified by'),
						TextEntry::make('approved_at')
							->dateTime(),
						TextEntry::make('rejectedBy.name')
							->label('Rejected by'),
						TextEntry::make('rejected_at')
							->dateTime(),
					])
					->columns(2),
			])
			->columns(1);
	}

	public static function table(Table $table): Table
	{
		return $table
			->defaultSort('created_at', 'desc')
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
					->toggleable(isToggledHiddenByDefault: true)
					->searchable()
					->simpleLightbox(),
				Tables\Columns\TextColumn::make('document_type')
					->searchable(),
				Tables\Columns\TextColumn::make('document_number')
					->searchable(),
				Tables\Columns\ImageColumn::make('document_front')
					->toggleable(isToggledHiddenByDefault: true)
					->searchable()
					->simpleLightbox(),
				Tables\Columns\ImageColumn::make('document_back')
					->toggleable(isToggledHiddenByDefault: true)
					->searchable()
					->simpleLightbox(),
				Tables\Columns\ImageColumn::make('selfie')
					->toggleable(isToggledHiddenByDefault: true)
					->searchable()
					->simpleLightbox(),
				Tables\Columns\TextColumn::make('status')
					->badge()
					->color(fn(string $state): string => match ($state) {
						'pending' => 'warning',
						'verified' => 'success',
						'rejected' => 'danger',
						default => 'gray',
					})
					->searchable(),
				Tables\Columns\TextColumn::make('verifiedBy.name')
					->label('Verified by')
					->searchable(),
				Tables\Columns\TextColumn::make('rejectedBy.name')
					->label('Rejected by')
					->searchable(),
				Tables\Columns\TextColumn::make('approved_at')
					->dateTime()
					->toggleable(isToggledHiddenByDefault: true)
					->sortable(),
				Tables\Columns\TextColumn::make('rejected_at')
					->dateTime()
					->toggleable(isToggledHiddenByDefault: true)
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
				Tables\Actions\ViewAction::make(),
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
			'view' => Pages\ViewVerification::route('/{record}'),
			'edit' => Pages\EditVerification::route('/{record}/edit'),
		];
	}
}
