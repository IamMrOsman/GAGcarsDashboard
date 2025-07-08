<?php

namespace App\Filament\Resources\ItemFieldResource\Pages;

use App\Filament\Resources\ItemFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditItemField extends EditRecord
{
    protected static string $resource = ItemFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
