<?php

namespace App\Filament\Widgets;

use App\Models\WalletLedger;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionsTable extends TableWidget
{
	protected int|string|array $columnSpan = 'full';

	protected static ?string $heading = 'Wallet Transactions';

	public function table(Table $table): Table
	{
		return $table
			->query($this->getQuery())
			->defaultPaginationPageOption(25)
			->columns([
				TextColumn::make('user.name')
					->label('User')
					->searchable()
					->sortable(),

				BadgeColumn::make('direction')
					->label('Direction')
					->colors([
						'success' => 'credit',
						'danger' => 'debit',
					])
					->formatStateUsing(fn (?string $state) => strtoupper((string) $state)),

				TextColumn::make('amount')
					->label('Amount')
					->money('GHC')
					->sortable(),

				BadgeColumn::make('status')
					->label('Status')
					->colors([
						'gray' => 'pending',
						'success' => 'completed',
						'danger' => 'failed',
					])
					->formatStateUsing(fn (?string $state) => ucfirst((string) $state)),

				TextColumn::make('reason')
					->label('Reason')
					->toggleable()
					->wrap(),

				TextColumn::make('reference')
					->label('Reference')
					->copyable()
					->toggleable(isToggledHiddenByDefault: true),

				TextColumn::make('created_at')
					->label('Date')
					->dateTime()
					->sortable(),
			]);
	}

	private function getQuery(): Builder
	{
		return WalletLedger::query()
			->with('user')
			->latest()
			->limit(200);
	}
}

