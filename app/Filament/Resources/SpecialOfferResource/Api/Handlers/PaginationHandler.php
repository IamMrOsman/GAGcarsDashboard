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

		// Filter by user's country_id - only show special offers for items in the same country
		if (auth()->check() && auth()->user()->country_id) {
			$query->whereHas('item', function ($q) {
				$q->where('status', 'active');
				$q->where('country_id', auth()->user()->country_id);
			});
		}

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
