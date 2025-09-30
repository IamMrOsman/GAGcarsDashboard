<?php
namespace App\Filament\Clusters\Faq\Resources\CountryResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\CountryResource;
use App\Filament\Clusters\Faq\Resources\CountryResource\Api\Transformers\CountryTransformer;

class DetailHandler extends Handlers {
    public static string | null $uri = '/{id}';
    public static string | null $resource = CountryResource::class;


    /**
     * Get Country Detail
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function handler()
    {
        $id = request()->route('id');
        $query = static::getEloquentQuery();

        $query = $query->where('id', $id)->first();

        if (!$query) {
            return response()->json(['message' => 'Country not found'], 404);
        }

        return new CountryTransformer($query);
    }
}
