<?php

namespace App\Filament\Resources\BrandResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\BrandResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\BrandResource\Api\Transformers\BrandTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = BrandResource::class;


    /**
     * Show Brand
     *
     * @param Request $request
     * @return BrandTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');

        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
            ->first();

        if (!$query) return static::sendNotFoundResponse();

        return new BrandTransformer($query);
    }
}
