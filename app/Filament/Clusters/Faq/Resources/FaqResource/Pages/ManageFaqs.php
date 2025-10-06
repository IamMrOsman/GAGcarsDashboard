<?php

namespace App\Filament\Clusters\Faq\Resources\FaqResource\Pages;

use App\Filament\Clusters\Faq\Resources\FaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFaqs extends ManageRecords
{
	protected static string $resource = FaqResource::class;

	protected function getHeaderActions(): array
	{
		return [
			Actions\CreateAction::make(),
		];
	}
}
