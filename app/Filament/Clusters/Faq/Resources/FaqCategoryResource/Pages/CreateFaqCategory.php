<?php

namespace App\Filament\Clusters\Faq\Resources\FaqCategoryResource\Pages;

use App\Filament\Clusters\Faq\Resources\FaqCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFaqCategory extends CreateRecord
{
	protected static string $resource = FaqCategoryResource::class;
}
