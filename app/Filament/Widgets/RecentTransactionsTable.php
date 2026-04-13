<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentTransactionsTable extends TableWidget
{
	protected static ?int $sort = 11;
	protected int|string|array $columnSpan = 'full';

	public function table(Table $table): Table
	{
		return $table
			->query(Transaction::with(['user', 'package'])->latest()->limit(10))
			->recordUrl(fn (): string => TransactionResource::getUrl('index'))
			->columns([
				TextColumn::make('user.name')
					->label('User')
					->searchable()
					->sortable(),
				TextColumn::make('package.name')
					->label('Package')
					->searchable()
					->sortable(),
				TextColumn::make('amount')
					->label('Amount')
					->money('GHC')
					->sortable(),
				TextColumn::make('created_at')
					->label('Date')
					->dateTime()
					->sortable(),
			])
			->heading('Recent Transactions');
	}
}
