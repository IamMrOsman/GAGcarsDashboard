<?php

namespace App\Filament\Resources\TransactionResource\Api\Handlers;

use App\Filament\Resources\TransactionResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\TransactionResource\Api\Transformers\TransactionTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = TransactionResource::class;


    /**
     * Show Transaction
     *
     * @param Request $request
     * @return TransactionTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');

        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
		->with(['user', 'package', 'item'])
            ->first();

        if (!$query) return static::sendNotFoundResponse();

        return new TransactionTransformer($query);
    }
}
