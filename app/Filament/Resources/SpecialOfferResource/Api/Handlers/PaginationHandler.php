<?php

namespace App\Filament\Resources\SpecialOfferResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use App\Filament\Resources\SpecialOfferResource;
use App\Filament\Resources\SpecialOfferResource\Api\Transformers\SpecialOfferTransformer;

class PaginationHandler extends Handlers
{
	public static string | null $uri = '/';
	public static string | null $resource = SpecialOfferResource::class;


	/**
	 * List of SpecialOffer
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
			->with(['item.user'])
			->paginate(request()->query('per_page'))
			->appends(request()->query());

		return SpecialOfferTransformer::collection($query);
	}
}
