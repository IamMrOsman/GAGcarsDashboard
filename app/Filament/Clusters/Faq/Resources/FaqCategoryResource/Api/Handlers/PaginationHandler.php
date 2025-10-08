<?php
namespace App\Filament\Clusters\Faq\Resources\FaqCategoryResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use App\Filament\Clusters\Faq\Resources\FaqCategoryResource;
use App\Filament\Clusters\Faq\Resources\FaqCategoryResource\Api\Transformers\FaqCategoryTransformer;

class PaginationHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = FaqCategoryResource::class;


    /**
     * List of FaqCategory
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
        ->with(['faqs'])
		->withCount('faqs')
        ->paginate(request()->query('per_page'))
        ->appends(request()->query());

        return FaqCategoryTransformer::collection($query);
    }
}
