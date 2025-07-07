<?php

namespace App\Filament\Resources\PostResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\PostResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\PostResource\Api\Transformers\PostTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = PostResource::class;


    /**
     * Show Post
     *
     * @param Request $request
     * @return PostTransformer
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

        return new PostTransformer($query);
    }
}
