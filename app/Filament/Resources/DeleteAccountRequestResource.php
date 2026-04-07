<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeleteAccountRequestResource\Pages;
use App\Models\DeleteAccountRequest;
use App\Services\DeleteAccountService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeleteAccountRequestResource extends Resource
{
	protected static ?string $model = DeleteAccountRequest::class;

	protected static ?string $navigationGroup = 'User';

	protected static ?string $navigationIcon = 'heroicon-o-user-minus';

	protected static ?string $recordTitleAttribute = 'id';

	public static function form(Form $form): Form
	{
		return $form->schema([]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('id')->label('Request ID')->searchable(),
				Tables\Columns\TextColumn::make('user_id')->label('User ID')->searchable(),
				Tables\Columns\BadgeColumn::make('status')
					->colors([
						'warning' => 'pending',
						'success' => 'approved',
						'danger' => 'rejected',
					]),
				Tables\Columns\TextColumn::make('requested_at')->dateTime()->sortable(),
				Tables\Columns\TextColumn::make('reviewed_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
				Tables\Columns\TextColumn::make('reviewed_by')->label('Reviewed by')->toggleable(isToggledHiddenByDefault: true),
			])
			->defaultSort('requested_at', 'desc')
			->filters([
				Tables\Filters\SelectFilter::make('status')
					->options([
						'pending' => 'Pending',
						'approved' => 'Approved',
						'rejected' => 'Rejected',
					]),
			])
			->actions([
				Tables\Actions\ViewAction::make(),
				Tables\Actions\Action::make('approve')
					->label('Approve')
					->requiresConfirmation()
					->visible(fn (DeleteAccountRequest $record) => $record->status === 'pending')
					->action(function (DeleteAccountRequest $record): void {
						$admin = auth()->user();
						if (! $admin) return;
						(new DeleteAccountService())->approve($record, $admin);
					}),
				Tables\Actions\Action::make('reject')
					->label('Reject')
					->requiresConfirmation()
					->visible(fn (DeleteAccountRequest $record) => $record->status === 'pending')
					->action(function (DeleteAccountRequest $record): void {
						$admin = auth()->user();
						if (! $admin) return;
						(new DeleteAccountService())->reject($record, $admin);
					}),
			]);
	}

	public static function getEloquentQuery(): Builder
	{
		return parent::getEloquentQuery();
	}

	public static function getPages(): array
	{
		return [
			'index' => Pages\ListDeleteAccountRequests::route('/'),
			'view' => Pages\ViewDeleteAccountRequest::route('/{record}'),
		];
	}
}

