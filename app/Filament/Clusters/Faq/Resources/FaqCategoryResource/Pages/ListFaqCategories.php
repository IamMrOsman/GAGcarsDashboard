<?php

namespace App\Filament\Clusters\Faq\Resources\FaqCategoryResource\Pages;

use App\Filament\Clusters\Faq\Resources\FaqCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaqCategories extends ListRecords
{
	protected static string $resource = FaqCategoryResource::class;

	protected function getHeaderActions(): array
	{
		return [
			Actions\CreateAction::make(),
		];
	}
}
