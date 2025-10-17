<?php

namespace App\Filament\Resources\BroadcastResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use App\Filament\Resources\BroadcastResource;
use App\Filament\Resources\BroadcastResource\Api\Transformers\BroadcastTransformer;

class PaginationHandler extends Handlers
{
	public static string | null $uri = '/';
	public static string | null $resource = BroadcastResource::class;


	/**
	 * List of Broadcasts
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
	 */
	public function handler()
	{
		$query = static::getEloquentQuery();

		$query = QueryBuilder::for($query)
			->allowedFields($this->getAllowedFields() ?? [])
			->allowedSorts($this->getAllowedSorts() ?? [])
			->allowedFilters($this->getAllowedFilters() ?? [])
			->allowedIncludes($this->getAllowedIncludes() ?? [])
			->with(['user', 'country'])
			->paginate(request()->query('per_page'))
			->appends(request()->query());

		return BroadcastTransformer::collection($query);
	}
}

