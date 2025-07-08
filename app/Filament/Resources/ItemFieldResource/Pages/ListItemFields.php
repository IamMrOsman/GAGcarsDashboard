<?php

namespace App\Filament\Resources\ItemFieldResource\Pages;

use App\Filament\Resources\ItemFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListItemFields extends ListRecords
{
    protected static string $resource = ItemFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
