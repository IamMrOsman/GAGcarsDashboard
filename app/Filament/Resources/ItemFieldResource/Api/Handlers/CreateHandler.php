<?php
namespace App\Filament\Resources\ItemFieldResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\ItemFieldResource;
use App\Filament\Resources\ItemFieldResource\Api\Requests\CreateItemFieldRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = ItemFieldResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create ItemField
     *
     * @param CreateItemFieldRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateItemFieldRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}