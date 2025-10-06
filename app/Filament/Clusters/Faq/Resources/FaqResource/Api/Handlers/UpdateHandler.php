<?php
namespace App\Filament\Clusters\Faq\Resources\FaqResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Clusters\Faq\Resources\FaqResource;
use App\Filament\Clusters\Faq\Resources\FaqResource\Api\Requests\UpdateFaqRequest;

class UpdateHandler extends Handlers {
    public static string | null $uri = '/{id}';
    public static string | null $resource = FaqResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }


    /**
     * Update Faq
     *
     * @param UpdateFaqRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(UpdateFaqRequest $request)
    {
        $id = $request->route('id');

        $model = static::getModel()::find($id);

        if (!$model) return static::sendNotFoundResponse();

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Update Resource");
    }
}