<?php
namespace App\Filament\Resources\ItemResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\ItemResource;
use App\Filament\Resources\ItemResource\Api\Requests\CreateItemRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = ItemResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Item
     *
     * @param CreateItemRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateItemRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

		$model->user_id = $request->user_id ?? auth()->id();

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}
