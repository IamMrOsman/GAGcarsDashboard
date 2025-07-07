<?php
namespace App\Filament\Resources\BrandModelResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\BrandModelResource;
use Illuminate\Routing\Router;


class BrandModelApiService extends ApiService
{
    protected static string | null $resource = BrandModelResource::class;

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
