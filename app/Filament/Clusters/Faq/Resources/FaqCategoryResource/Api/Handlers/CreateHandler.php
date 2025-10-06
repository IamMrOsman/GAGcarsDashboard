<?php
namespace App\Filament\Clusters\Faq\Resources\FaqCategoryResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Clusters\Faq\Resources\FaqCategoryResource;
use App\Filament\Clusters\Faq\Resources\FaqCategoryResource\Api\Requests\CreateFaqCategoryRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = FaqCategoryResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create FaqCategory
     *
     * @param CreateFaqCategoryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateFaqCategoryRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}