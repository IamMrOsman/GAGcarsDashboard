<?php
namespace App\Filament\Resources\FaqResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\FaqResource;
use Illuminate\Routing\Router;


class FaqApiService extends ApiService
{
    protected static string | null $resource = FaqResource::class;

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
