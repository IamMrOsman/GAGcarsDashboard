<?php

namespace App\Filament\Resources\DeleteAccountRequestResource\Pages;

use App\Filament\Resources\DeleteAccountRequestResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewDeleteAccountRequest extends ViewRecord
{
	protected static string $resource = DeleteAccountRequestResource::class;

	public function infolist(Infolist $infolist): Infolist
	{
		return $infolist->schema([
			Section::make('Request')
				->schema([
					TextEntry::make('id')->label('Request ID'),
					TextEntry::make('user_id')->label('User ID'),
					TextEntry::make('status'),
					TextEntry::make('requested_at')->dateTime(),
					TextEntry::make('reviewed_at')->dateTime(),
					TextEntry::make('reviewed_by'),
					TextEntry::make('reason')->columnSpanFull(),
				]),
			Section::make('Snapshot')
				->schema([
					TextEntry::make('snapshot')
						->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
						->columnSpanFull(),
				]),
		]);
	}
}

