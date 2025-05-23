<?php
namespace App\Filament\Resources\SpecialOfferResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\SpecialOfferResource;
use Illuminate\Routing\Router;


class SpecialOfferApiService extends ApiService
{
    protected static string | null $resource = SpecialOfferResource::class;

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
