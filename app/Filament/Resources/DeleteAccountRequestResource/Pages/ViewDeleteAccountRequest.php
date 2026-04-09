<?php

namespace App\Filament\Resources\DeleteAccountRequestResource\Pages;

use App\Filament\Resources\DeleteAccountRequestResource;
use App\Models\DeleteAccountRequest;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

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
					TextEntry::make('snapshot_summary')
						->label('Summary')
						->state(function (DeleteAccountRequest $record): string {
							$s = is_array($record->snapshot) ? ($record->snapshot['summary'] ?? []) : [];
							if (! is_array($s)) {
								return '';
							}

							$uploadsLeft = $s['uploads_left'] ?? null;
							if (is_array($uploadsLeft)) {
								$uploadsLeft = json_encode($uploadsLeft, JSON_UNESCAPED_SLASHES);
							}

							$listings = $s['listings'] ?? [];
							$wallet = $s['wallet'] ?? [];
							$tx = $s['transactions'] ?? [];

							return implode(\"\\n\", array_filter([\n+\t\t\t\t\t\t\t\t\"Profile photo: \" . (string) ($s['profile_photo'] ?? ''),\n+\t\t\t\t\t\t\t\t\"Uploads left: \" . (is_string($uploadsLeft) ? $uploadsLeft : (string) $uploadsLeft),\n+\t\t\t\t\t\t\t\t\"Listings: total=\" . (string) ($listings['total'] ?? '') . \", active=\" . (string) ($listings['active'] ?? '') . \", expired=\" . (string) ($listings['expired'] ?? '') . \", sold=\" . (string) ($listings['sold'] ?? ''),\n+\t\t\t\t\t\t\t\t\"Wallet balance: \" . (string) ($wallet['balance'] ?? ''),\n+\t\t\t\t\t\t\t\t\"Transactions: total=\" . (string) ($tx['total'] ?? '') . \", wallet_topups=\" . (string) ($tx['wallet_topups'] ?? ''),\n+\t\t\t\t\t\t\t]));\n+\t\t\t\t\t\t})\n+\t\t\t\t\t\t->columnSpanFull(),
					TextEntry::make('snapshot_json')
						->label('Snapshot (JSON)')
						->state(function (DeleteAccountRequest $record): string {
							$state = $record->snapshot;
							if ($state === null) {
								return '';
							}
							if (is_string($state)) {
								return $state;
							}
							$json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

							return is_string($json) ? $json : Str::of(var_export($state, true))->toString();
						})
						->markdown()
						->columnSpanFull(),
				]),
		]);
	}
}

