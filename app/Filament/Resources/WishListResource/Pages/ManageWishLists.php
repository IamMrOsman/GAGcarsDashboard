<?php

namespace App\Filament\Resources\WishListResource\Pages;

use App\Filament\Resources\WishListResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWishLists extends ManageRecords
{
	protected static string $resource = WishListResource::class;

	protected function getHeaderActions(): array
	{
		return [
			// Actions\CreateAction::make(),
		];
	}
}
