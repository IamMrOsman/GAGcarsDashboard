<?php
namespace App\Filament\Resources\ItemResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\ItemResource;
use Illuminate\Routing\Router;


class ItemApiService extends ApiService
{
    protected static string | null $resource = ItemResource::class;

    public static function handlers() : array
    {
        return [
            Handlers\CreateHandler::class,
            Handlers\UpdateHandler::class,
            Handlers\DeleteHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class
        ];

    }
}
