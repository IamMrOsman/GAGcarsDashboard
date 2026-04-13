<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\VerificationResource;
use App\Models\Verification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingVerificationsTable extends TableWidget
{
	protected static ?int $sort = 12;
	protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

	public function table(Table $table): Table
	{
		return $table
			->query(Verification::with('user')->where('status', 'pending')->latest()->limit(5))
			->recordUrl(fn (Verification $record): string => VerificationResource::getUrl('view', ['record' => $record]))
			->columns([
				TextColumn::make('user.name')
					->label('User')
					->searchable()
					->sortable(),
				TextColumn::make('verification_type')
					->label('Type')
					->badge()
					->color(fn(string $state): string => match ($state) {
						'individual' => 'info',
						'dealer' => 'warning',
						default => 'gray',
					}),
				TextColumn::make('created_at')
					->label('Requested')
					->dateTime()
					->sortable(),
			])
			->heading('Pending Verifications');
	}
}
