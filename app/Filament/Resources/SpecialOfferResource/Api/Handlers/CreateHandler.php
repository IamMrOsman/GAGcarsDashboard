<?php
namespace App\Filament\Resources\SpecialOfferResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\SpecialOfferResource;
use App\Filament\Resources\SpecialOfferResource\Api\Requests\CreateSpecialOfferRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = SpecialOfferResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create SpecialOffer
     *
     * @param CreateSpecialOfferRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateSpecialOfferRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}