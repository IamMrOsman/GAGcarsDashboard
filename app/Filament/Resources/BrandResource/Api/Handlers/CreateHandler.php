<?php
namespace App\Filament\Resources\BrandResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\BrandResource;
use App\Filament\Resources\BrandResource\Api\Requests\CreateBrandRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = BrandResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Brand
     *
     * @param CreateBrandRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateBrandRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}