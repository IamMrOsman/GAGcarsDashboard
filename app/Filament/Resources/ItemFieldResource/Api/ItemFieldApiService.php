<?php
namespace App\Filament\Resources\ItemFieldResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\ItemFieldResource;
use Illuminate\Routing\Router;


class ItemFieldApiService extends ApiService
{
    protected static string | null $resource = ItemFieldResource::class;

    public static function handlers() : array
    {
        return [
            // Handlers\CreateHandler::class,
            // Handlers\UpdateHandler::class,
            // Handlers\DeleteHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class
        ];

    }
}
