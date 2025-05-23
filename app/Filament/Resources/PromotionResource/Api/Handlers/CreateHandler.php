<?php
namespace App\Filament\Resources\PromotionResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\PromotionResource;
use App\Filament\Resources\PromotionResource\Api\Requests\CreatePromotionRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = PromotionResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Promotion
     *
     * @param CreatePromotionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreatePromotionRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}