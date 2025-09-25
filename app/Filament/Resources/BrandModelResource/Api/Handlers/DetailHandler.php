<?php

namespace App\Filament\Resources\BrandModelResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\BrandModelResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\BrandModelResource\Api\Transformers\BrandModelTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = BrandModelResource::class;


    /**
     * Show BrandModel
     *
     * @param Request $request
     * @return BrandModelTransformer
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

        return new BrandModelTransformer($query);
    }
}
