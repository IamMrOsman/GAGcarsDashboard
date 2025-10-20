<?php

namespace App\Filament\Resources\BroadcastResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\BroadcastResource;
use Illuminate\Routing\Router;


class BroadcastApiService extends ApiService
{
	protected static string | null $resource = BroadcastResource::class;

	public static function handlers(): array
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
