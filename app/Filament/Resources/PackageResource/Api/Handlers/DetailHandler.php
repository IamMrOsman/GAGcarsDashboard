<?php

namespace App\Filament\Resources\PackageResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\PackageResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\PackageResource\Api\Transformers\PackageTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = PackageResource::class;


    /**
     * Show Package
     *
     * @param Request $request
     * @return PackageTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');

        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
            ->with(['category', 'country'])
            ->first();

        if (!$query) return static::sendNotFoundResponse();

        return new PackageTransformer($query);
    }
}
