<?php
namespace App\Filament\Resources\BrandModelResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\BrandModelResource;
use App\Filament\Resources\BrandModelResource\Api\Requests\CreateBrandModelRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = BrandModelResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create BrandModel
     *
     * @param CreateBrandModelRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateBrandModelRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}