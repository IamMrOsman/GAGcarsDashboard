<?php

namespace App\Filament\Resources\MarketerProfileResource\Pages;

use App\Filament\Resources\MarketerProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMarketerProfiles extends ManageRecords
{
    protected static string $resource = MarketerProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
