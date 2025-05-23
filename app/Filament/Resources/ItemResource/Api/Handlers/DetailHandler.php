<?php

namespace App\Filament\Resources\ItemResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\ItemResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\ItemResource\Api\Transformers\ItemTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = ItemResource::class;


    /**
     * Show Item
     *
     * @param Request $request
     * @return ItemTransformer
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

        return new ItemTransformer($query);
    }
}
