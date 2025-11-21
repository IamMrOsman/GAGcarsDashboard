<?php

namespace App\Filament\Resources\ItemResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use App\Filament\Resources\ItemResource;
use App\Filament\Resources\ItemResource\Api\Transformers\ItemTransformer;

class PaginationHandler extends Handlers
{
	public static string | null $uri = '/';
	public static string | null $resource = ItemResource::class;


	/**
	 * List of Item
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
			->with(['brand', 'category.itemFields', 'brandModel', 'user', 'promotions'])
			->where('status', 'active')
			->where('country_id', auth()->user()->country_id)
			->paginate(request()->query('per_page'))
			->appends(request()->query());

		// Duplicate promoted items and shuffle
		$items = $query->getCollection();
		$modifiedItems = collect();
		$now = now();

		foreach ($items as $item) {
			// Add the item once
			$modifiedItems->push($item);

			// Check if item is promoted using eager loaded promotions
			$isPromoted = $item->relationLoaded('promotions')
				? $item->promotions->where('start_at', '<=', $now)->where('end_at', '>=', $now)->isNotEmpty()
				: $item->isPromoted();

			// If promoted, add it 2 more times (3 total appearances)
			if ($isPromoted) {
				$modifiedItems->push($item);
				$modifiedItems->push($item);
				$modifiedItems->push($item);
				$modifiedItems->push($item);
			}
		}

		// Shuffle the collection randomly
		$modifiedItems = $modifiedItems->shuffle();

		// Update the pagination object with modified items
		$query->setCollection($modifiedItems);

		return ItemTransformer::collection($query);
	}
}
