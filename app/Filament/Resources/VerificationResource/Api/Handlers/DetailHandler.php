<?php

namespace App\Filament\Resources\VerificationResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\VerificationResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\VerificationResource\Api\Transformers\VerificationTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = VerificationResource::class;


    /**
     * Show Verification
     *
     * @param Request $request
     * @return VerificationTransformer
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

        return new VerificationTransformer($query);
    }
}
