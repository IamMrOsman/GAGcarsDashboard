<?php

namespace App\Filament\Resources\DeleteAccountRequestResource\Pages;

use App\Filament\Resources\DeleteAccountRequestResource;
use App\Models\DeleteAccountRequest;
use Filament\Infolists\Components\ImageEntry;
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
					Section::make('Account summary')
						->schema([
							ImageEntry::make('snapshot_profile_photo')
								->label('Profile image')
								->state(function (DeleteAccountRequest $record): ?string {
									$s = is_array($record->snapshot) ? ($record->snapshot['summary'] ?? null) : null;
									if (! is_array($s)) {
										return null;
									}
									$url = (string) ($s['profile_photo'] ?? '');

									return $url !== '' ? $url : null;
								})
								->height(140)
								->simpleLightbox()
								->columnSpan(1),

							TextEntry::make('snapshot_uploads_left')
								->label('Uploads left')
								->state(function (DeleteAccountRequest $record): string {
									$s = is_array($record->snapshot) ? ($record->snapshot['summary'] ?? null) : null;
									if (! is_array($s)) {
										return '';
									}
									$uploadsLeft = $s['uploads_left'] ?? null;
									if (is_array($uploadsLeft)) {
										$json = json_encode($uploadsLeft, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
										return is_string($json) ? $json : '';
									}
									return is_string($uploadsLeft) ? $uploadsLeft : (string) $uploadsLeft;
								})
								->markdown()
								->columnSpan(1),

							TextEntry::make('snapshot_listings')
								->label('Listings')
								->state(function (DeleteAccountRequest $record): string {
									$s = is_array($record->snapshot) ? ($record->snapshot['summary'] ?? null) : null;
									if (! is_array($s)) {
										return '';
									}
									$l = $s['listings'] ?? [];
									if (! is_array($l)) {
										return '';
									}

									return implode("\n", [
										'Total: ' . (string) ($l['total'] ?? 0),
										'Active: ' . (string) ($l['active'] ?? 0),
										'Expired: ' . (string) ($l['expired'] ?? 0),
										'Sold: ' . (string) ($l['sold'] ?? 0),
									]);
								})
								->markdown()
								->columnSpan(1),

							TextEntry::make('snapshot_wallet_balance')
								->label('Wallet balance')
								->state(function (DeleteAccountRequest $record): string {
									$s = is_array($record->snapshot) ? ($record->snapshot['summary'] ?? null) : null;
									if (! is_array($s)) {
										return '';
									}
									$w = $s['wallet'] ?? [];
									if (! is_array($w)) {
										return '';
									}
									return (string) ($w['balance'] ?? '');
								})
								->columnSpan(1),

							TextEntry::make('snapshot_transactions')
								->label('Transactions')
								->state(function (DeleteAccountRequest $record): string {
									$s = is_array($record->snapshot) ? ($record->snapshot['summary'] ?? null) : null;
									if (! is_array($s)) {
										return '';
									}
									$t = $s['transactions'] ?? [];
									if (! is_array($t)) {
										return '';
									}

									return implode("\n", [
										'Total: ' . (string) ($t['total'] ?? 0),
										'Wallet topups: ' . (string) ($t['wallet_topups'] ?? 0),
									]);
								})
								->markdown()
								->columnSpan(1),
						])
						->columns(2)
						->columnSpanFull(),

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

