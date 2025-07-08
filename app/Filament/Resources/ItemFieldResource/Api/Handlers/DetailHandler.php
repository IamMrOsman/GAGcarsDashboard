<?php

namespace App\Filament\Resources\ItemFieldResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\ItemFieldResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\ItemFieldResource\Api\Transformers\ItemFieldTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = ItemFieldResource::class;


    /**
     * Show ItemField
     *
     * @param Request $request
     * @return ItemFieldTransformer
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

        return new ItemFieldTransformer($query);
    }
}
