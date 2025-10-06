<?php
namespace App\Filament\Clusters\Faq\Resources\FaqResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Clusters\Faq\Resources\FaqResource;
use App\Filament\Clusters\Faq\Resources\FaqResource\Api\Requests\CreateFaqRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = FaqResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Faq
     *
     * @param CreateFaqRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateFaqRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}