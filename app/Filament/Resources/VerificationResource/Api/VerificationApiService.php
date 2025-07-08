<?php
namespace App\Filament\Resources\VerificationResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\VerificationResource;
use Illuminate\Routing\Router;


class VerificationApiService extends ApiService
{
    protected static string | null $resource = VerificationResource::class;

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
