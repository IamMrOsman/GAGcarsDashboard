<?php

namespace App\Filament\Resources\VerificationResource\Pages;

use App\Filament\Resources\VerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListVerifications extends ListRecords
{
	protected static string $resource = VerificationResource::class;

	protected function getHeaderActions(): array
	{
		return [
			Actions\CreateAction::make(),
		];
	}

	public function getTabs(): array
	{
		return [
			'all' => Tab::make('All')
				->badge(VerificationResource::getEloquentQuery()->count()),
			'pending' => Tab::make('Pending')
				->badge(VerificationResource::getEloquentQuery()->where('status', 'pending')->count())
				->modifyQueryUsing(fn($query) => $query->where('status', 'pending')),
			'verified' => Tab::make('Verified')
				->badge(VerificationResource::getEloquentQuery()->where('status', 'verified')->count())
				->modifyQueryUsing(fn($query) => $query->where('status', 'verified')),
			'rejected' => Tab::make('Rejected')
				->badge(VerificationResource::getEloquentQuery()->where('status', 'rejected')->count())
				->modifyQueryUsing(fn($query) => $query->where('status', 'rejected')),
		];
	}
}
