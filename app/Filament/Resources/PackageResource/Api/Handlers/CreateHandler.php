<?php
namespace App\Filament\Resources\PackageResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\PackageResource;
use App\Filament\Resources\PackageResource\Api\Requests\CreatePackageRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = PackageResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Package
     *
     * @param CreatePackageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreatePackageRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}