<?php
namespace App\Filament\Resources\PackageResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\PackageResource;
use Illuminate\Routing\Router;


class PackageApiService extends ApiService
{
    protected static string | null $resource = PackageResource::class;

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
