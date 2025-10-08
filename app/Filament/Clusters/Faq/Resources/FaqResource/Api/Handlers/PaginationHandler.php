<?php
namespace App\Filament\Clusters\Faq\Resources\FaqResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use App\Filament\Clusters\Faq\Resources\FaqResource;
use App\Filament\Clusters\Faq\Resources\FaqResource\Api\Transformers\FaqTransformer;

class PaginationHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = FaqResource::class;


    /**
     * List of Faq
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
        ->with(['user', 'category'])
        ->paginate(request()->query('per_page'))
        ->appends(request()->query());

        return FaqTransformer::collection($query);
    }
}
