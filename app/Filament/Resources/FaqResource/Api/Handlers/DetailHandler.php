<?php

namespace App\Filament\Resources\FaqResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\FaqResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\FaqResource\Api\Transformers\FaqTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = FaqResource::class;


    /**
     * Show Faq
     *
     * @param Request $request
     * @return FaqTransformer
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

        return new FaqTransformer($query);
    }
}
