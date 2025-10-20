<?php

namespace App\Filament\Resources\BroadcastResource\Api\Handlers;

use App\Filament\Resources\BroadcastResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\BroadcastResource\Api\Transformers\BroadcastTransformer;

class DetailHandler extends Handlers
{
	public static string | null $uri = '/{id}';
	public static string | null $resource = BroadcastResource::class;


	/**
	 * Show Broadcast
	 *
	 * @param Request $request
	 * @return BroadcastTransformer|\Illuminate\Http\JsonResponse
	 */
	public function handler(Request $request)
	{
		$id = $request->route('id');

		$query = static::getEloquentQuery();

		$query = QueryBuilder::for(
			$query->where(static::getKeyName(), $id)
		)
			->with(['user', 'country'])
			->first();

		if (!$query) return static::sendNotFoundResponse();

		return new BroadcastTransformer($query);
	}
}
