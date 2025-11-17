<?php
namespace App\Filament\Resources\ItemResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use App\Filament\Resources\ItemResource;
use App\Filament\Resources\ItemResource\Api\Transformers\ItemTransformer;

class PaginationHandler extends Handlers {
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
		->with(['brand', 'category', 'brandModel', 'user'])
        ->where('status', 'active')
		->where('country_id', auth()->user()->country_id)
        ->paginate(request()->query('per_page'))
        ->appends(request()->query());

        return ItemTransformer::collection($query);
    }
}
