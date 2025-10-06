<?php
namespace App\Filament\Clusters\Faq\Resources\FaqCategoryResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Clusters\Faq\Resources\FaqCategoryResource;
use App\Filament\Clusters\Faq\Resources\FaqCategoryResource\Api\Requests\UpdateFaqCategoryRequest;

class UpdateHandler extends Handlers {
    public static string | null $uri = '/{id}';
    public static string | null $resource = FaqCategoryResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }


    /**
     * Update FaqCategory
     *
     * @param UpdateFaqCategoryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(UpdateFaqCategoryRequest $request)
    {
        $id = $request->route('id');

        $model = static::getModel()::find($id);

        if (!$model) return static::sendNotFoundResponse();

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Update Resource");
    }
}