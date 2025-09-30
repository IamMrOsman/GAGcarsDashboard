<?php
namespace App\Filament\Clusters\Faq\Resources\CountryResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\CountryResource;
use Illuminate\Routing\Router;


class CountryApiService extends ApiService
{
    protected static string | null $resource = CountryResource::class;

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
