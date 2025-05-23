<?php
namespace App\Filament\Resources\PromotionResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\PromotionResource;
use Illuminate\Routing\Router;


class PromotionApiService extends ApiService
{
    protected static string | null $resource = PromotionResource::class;

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
