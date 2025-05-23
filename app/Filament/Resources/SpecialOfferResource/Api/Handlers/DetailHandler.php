<?php

namespace App\Filament\Resources\SpecialOfferResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\SpecialOfferResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\SpecialOfferResource\Api\Transformers\SpecialOfferTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = SpecialOfferResource::class;


    /**
     * Show SpecialOffer
     *
     * @param Request $request
     * @return SpecialOfferTransformer
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

        return new SpecialOfferTransformer($query);
    }
}
