<?php

namespace App\Filament\Resources\BrandResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use App\Filament\Resources\BrandResource;
use App\Filament\Resources\BrandResource\Api\Transformers\BrandTransformer;

class PaginationHandler extends Handlers
{
	public static string | null $uri = '/';
	public static string | null $resource = BrandResource::class;


	/**
	 * List of Brand
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
			->with(['brandModels'])
			->withCount(['items' => function ($query) {
				// Add your custom conditions here
				// Example: exclude soft-deleted items
				$query->whereNull('deleted_at')->where('status', 'active');
				// Example: only count active items
				// $query->where('status', 'active');
			}])
			// ->paginate(request()->query('per_page'))
			->paginate(200)
			->appends(request()->query());

		return BrandTransformer::collection($query);
	}
}
